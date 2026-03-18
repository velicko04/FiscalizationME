<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GenerateContractInvoices extends Command
{
    protected $signature = 'invoices:generate';
    protected $description = 'Generate invoices from active contracts';

    public function handle()
    {
        $today = Carbon::today();
        $currentDay = $today->day;
        $currentMonth = $today->month;
        $currentYear = $today->year;

        $this->info("=== Pokretanje generisanja faktura: {$today->toDateString()} ===");
        Log::info('=== AUTOMATSKO GENERISANJE FAKTURA START ===', ['date' => $today->toDateString()]);

        $contracts = Contract::with(['items.product.vatRate', 'company.users', 'buyer'])
            ->where('status', 'active')
            ->where('issue_day', $currentDay)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get();

        $this->info("Pronađeno aktivnih ugovora sa issue_day={$currentDay}: {$contracts->count()}");

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($contracts as $contract) {
            try {
                // Provjeri billing_frequency
                if (!$this->shouldGenerateToday($contract, $today)) {
                    $this->line("  SKIP [{$contract->contract_number}] - nije period za fakturisanje");
                    $skipped++;
                    continue;
                }

                // Provjeri da li faktura već postoji za ovaj period
                if ($this->invoiceExistsForPeriod($contract, $today)) {
                    $this->line("  SKIP [{$contract->contract_number}] - faktura za ovaj period već postoji");
                    $skipped++;
                    continue;
                }

                // Izračunaj iznose
                $totalWithoutVat = 0;
                $totalVat = 0;

                foreach ($contract->items as $item) {
                    $base = round($item->quantity * $item->unit_price, 2);
                    $vatRate = $item->vatRate->percentage ?? 0;
                    $vat = round($base * ($vatRate / 100), 2);
                    $totalWithoutVat += $base;
                    $totalVat += $vat;
                }

                $totalWithVat = round($totalWithoutVat + $totalVat, 2);
                $totalWithoutVat = round($totalWithoutVat, 2);
                $totalVat = round($totalVat, 2);

                // Generiši broj fakture
                $orderNumber = Invoice::whereYear('issued_at', $currentYear)->count() + 1;
                $buCode = strtolower($contract->company->business_unit_code ?? '');
                $enuCode = strtolower($contract->company->enu_code ?? '');
                $invoiceNumber = "{$orderNumber}/{$currentYear}/{$buCode}/{$enuCode}";

                // Uzmi prvog korisnika kompanije
                $userId = $contract->company->users->first()->id ?? 1;

                // Kreiraj fakturu
                $invoice = Invoice::create([
                    'invoice_number'          => $invoiceNumber,
                    'order_number'            => $orderNumber,
                    'invoice_type'            => 'INVOICE',
                    'type_of_invoice'         => 'NONCASH',
                    'issued_at'               => $today,
                    'tax_period'              => $today->format('m/Y'),
                    'total_price_without_vat' => $totalWithoutVat,
                    'payment_method_type'     => 'CARD',
                    'total_vat_amount'        => $totalVat,
                    'total_price_to_pay'      => $totalWithVat,
                    'company_id'              => $contract->company_id,
                    'buyer_id'                => $contract->buyer_id,
                    'user_id'                 => $userId,
                    'contract_id'             => $contract->id,
                    'created_at'              => now(),
                ]);

                // Kreiraj stavke fakture
                foreach ($contract->items as $item) {
                    InvoiceItem::create([
                        'invoice_id'  => $invoice->id,
                        'product_id'  => $item->product_id,
                        'quantity'    => $item->quantity,
                        'unit_price'  => $item->unit_price,
                        'vat_rate_id' => $item->vat_rate_id,
                    ]);
                }

                $this->info("  OK [{$contract->contract_number}] - kreirana faktura {$invoiceNumber} ({$totalWithVat} EUR)");
                Log::info('Faktura kreirana', [
                    'contract'       => $contract->contract_number,
                    'invoice_number' => $invoiceNumber,
                    'total'          => $totalWithVat,
                ]);

                $generated++;

            } catch (\Throwable $e) {
                $failed++;
                $this->error("  GREŠKA [{$contract->contract_number}] - {$e->getMessage()}");
                Log::error('Greška pri generisanju fakture', [
                    'contract' => $contract->contract_number,
                    'error'    => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("=== ZAVRŠENO: generisano={$generated}, preskočeno={$skipped}, greške={$failed} ===");
        Log::info('=== AUTOMATSKO GENERISANJE FAKTURA END ===', [
            'generated' => $generated,
            'skipped'   => $skipped,
            'failed'    => $failed,
        ]);
    }

    private function shouldGenerateToday(Contract $contract, Carbon $today): bool
    {
        switch ($contract->billing_frequency) {
            case 'monthly':
                // Svaki mjesec na issue_day - uvijek true jer smo već filtrirali po issue_day
                return true;

            case 'quarterly':
                // Svaka 3 mjeseca - provjeri zadnju fakturu
                $lastInvoice = Invoice::where('contract_id', $contract->id)
                    ->orderBy('issued_at', 'desc')
                    ->first();

                if (!$lastInvoice) {
                    return true;
                }

                $lastDate = Carbon::parse($lastInvoice->issued_at);
                return $lastDate->diffInMonths($today) >= 3;

            case 'yearly':
                // Jednom godišnje
                $lastInvoice = Invoice::where('contract_id', $contract->id)
                    ->orderBy('issued_at', 'desc')
                    ->first();

                if (!$lastInvoice) {
                    return true;
                }

                $lastDate = Carbon::parse($lastInvoice->issued_at);
                return $lastDate->diffInMonths($today) >= 12;

            default:
                return false;
        }
    }

    private function invoiceExistsForPeriod(Contract $contract, Carbon $today): bool
    {
        switch ($contract->billing_frequency) {
            case 'monthly':
                return Invoice::where('contract_id', $contract->id)
                    ->whereMonth('issued_at', $today->month)
                    ->whereYear('issued_at', $today->year)
                    ->exists();

            case 'quarterly':
                // Provjeri da li postoji faktura u zadnja 3 mjeseca
                return Invoice::where('contract_id', $contract->id)
                    ->where('issued_at', '>=', $today->copy()->subMonths(3))
                    ->exists();

            case 'yearly':
                return Invoice::where('contract_id', $contract->id)
                    ->whereYear('issued_at', $today->year)
                    ->exists();

            default:
                return false;
        }
    }
}