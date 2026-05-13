<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;

trait ChatQueryActionsTrait
{
    private function isCreateContractRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        if (str_contains($normalizedMessage, 'faktura') || str_contains($normalizedMessage, 'fakturu') || str_contains($normalizedMessage, 'invoice')) {
            return false;
        }
    
        return (
            str_contains($normalizedMessage, 'napravi')
            || str_contains($normalizedMessage, 'kreiraj')
            || str_contains($normalizedMessage, 'dodaj')
            || str_contains($normalizedMessage, 'fakturisi')
            || str_contains($normalizedMessage, 'fakturiši')
            || str_contains($normalizedMessage, 'create')
            || str_contains($normalizedMessage, 'make')
            || str_contains($normalizedMessage, 'add')
        ) && (
            str_contains($normalizedMessage, 'ugovor')
            || str_contains($normalizedMessage, 'contract')
        );
    }
    
    private function isCreateInvoiceRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return (
            str_contains($normalizedMessage, 'napravi')
            || str_contains($normalizedMessage, 'kreiraj')
            || str_contains($normalizedMessage, 'dodaj')
            || str_contains($normalizedMessage, 'create')
            || str_contains($normalizedMessage, 'make')
            || str_contains($normalizedMessage, 'add')
        ) && (
            str_contains($normalizedMessage, 'faktura')
            || str_contains($normalizedMessage, 'fakturu')
            || str_contains($normalizedMessage, 'invoice')
        );
    }
    
    private function isContractStatusSummaryRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return (
            str_contains($normalizedMessage, 'status')
            || str_contains($normalizedMessage, 'aktiv')
            || str_contains($normalizedMessage, 'neaktiv')
            || str_contains($normalizedMessage, 'active')
            || str_contains($normalizedMessage, 'inactive')
            || str_contains($normalizedMessage, 'istek')
            || str_contains($normalizedMessage, 'expired')
            || str_contains($normalizedMessage, 'koliko')
        ) && (
            str_contains($normalizedMessage, 'ugovor')
            || str_contains($normalizedMessage, 'ugovora')
            || str_contains($normalizedMessage, 'contracts')
            || str_contains($normalizedMessage, 'contract')
        ) && $this->extractContractNumber($message) === null;
    }
    
    private function handleContractStatusSummaryRequest(): string
    {
        $contracts = \App\Models\Contract::query()
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
    
        $total = (int) $contracts->sum();
        $active = (int) ($contracts['active'] ?? 0);
        $paused = (int) ($contracts['paused'] ?? 0);
        $expired = (int) ($contracts['expired'] ?? 0);
        $other = max(0, $total - $active - $paused - $expired);
    
        $lines = [
            "Ukupno ugovora: {$total}",
            "Aktivni: {$active}",
            "Pauzirani/neaktivni: {$paused}",
            "Istekli: {$expired}",
        ];
    
        if ($other > 0) {
            $lines[] = "Ostali statusi: {$other}";
        }
    
        return "Statusi ugovora:\n- " . implode("\n- ", $lines);
    }
    
    private function isCompanyListRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return (
            str_contains($normalizedMessage, 'lista')
            || str_contains($normalizedMessage, 'listu')
            || str_contains($normalizedMessage, 'prikazi')
            || str_contains($normalizedMessage, 'prikaži')
            || str_contains($normalizedMessage, 'daj')
            || str_contains($normalizedMessage, 'show')
            || str_contains($normalizedMessage, 'all')
            || str_contains($normalizedMessage, 'sve')
        ) && (
            str_contains($normalizedMessage, 'firma')
            || str_contains($normalizedMessage, 'firme')
            || str_contains($normalizedMessage, 'kompanija')
            || str_contains($normalizedMessage, 'kompanije')
            || str_contains($normalizedMessage, 'company')
            || str_contains($normalizedMessage, 'companies')
        );
    }
    
    private function handleCompanyListRequest(): string
    {
        $companies = \App\Models\Company::query()
            ->orderBy('name')
            ->get();
    
        if ($companies->isEmpty()) {
            return 'U sistemu nema firmi.';
        }
    
        $lines = $companies->map(function ($company, $index) {
            $city = $company->city ?: '-';
            $taxId = $company->tax_id_number ?: '-';
    
            return ($index + 1) . ". {$company->name} | PIB: {$taxId} | Grad: {$city}";
        })->join("\n");
    
        return "Lista firmi ({$companies->count()}):\n{$lines}";
    }
    
    private function isUnfiscalizedInvoicesRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return (
            str_contains($normalizedMessage, 'nefiskal')
            || str_contains($normalizedMessage, 'nije fisk')
            || str_contains($normalizedMessage, 'nisu fisk')
            || str_contains($normalizedMessage, 'čeka fisk')
            || str_contains($normalizedMessage, 'ceka fisk')
            || str_contains($normalizedMessage, 'unfiscal')
            || str_contains($normalizedMessage, 'not fiscal')
        ) && (
            str_contains($normalizedMessage, 'faktura')
            || str_contains($normalizedMessage, 'fakture')
            || str_contains($normalizedMessage, 'račun')
            || str_contains($normalizedMessage, 'racun')
            || str_contains($normalizedMessage, 'invoice')
        );
    }
    
    private function handleUnfiscalizedInvoicesRequest(string $message): string
    {
        $contract = $this->findContractForChat($message);
        if ($contract) {
            $this->rememberChatContext($contract);
        }
    
        if ($contract && $this->isLastInvoiceQuestion($message)) {
            $invoice = \App\Models\Invoice::with(['company', 'buyer', 'contract'])
                ->where('contract_id', $contract->id)
                ->orderByDesc('issued_at')
                ->orderByDesc('id')
                ->first();
    
            if (!$invoice) {
                return "Ugovor {$contract->contract_number} nema fakture.";
            }
            $this->rememberChatContext($contract, $invoice);
    
            if ($invoice->fic) {
                return "Da, zadnja faktura za ugovor {$contract->contract_number} je fiskalizovana.\nBroj fakture: {$invoice->invoice_number}\nDatum: {$invoice->issued_at->format('Y-m-d')}\nFIC: {$invoice->fic}";
            }
    
            return "Ne, zadnja faktura za ugovor {$contract->contract_number} nije fiskalizovana.\nBroj fakture: {$invoice->invoice_number}\nDatum: {$invoice->issued_at->format('Y-m-d')}\nUkupno za plaćanje: {$invoice->total_price_to_pay} EUR";
        }
    
        $invoices = \App\Models\Invoice::with(['company', 'buyer', 'contract'])
            ->whereNull('fic')
            ->where('invoice_type', '!=', 'CORRECTIVE')
            ->when($contract, fn($query) => $query->where('contract_id', $contract->id))
            ->orderBy('issued_at', 'desc')
            ->get();
    
        if ($invoices->isEmpty()) {
            return $contract
                ? "Ugovor {$contract->contract_number} nema nefiskalizovanih faktura."
                : 'Nema nefiskalizovanih faktura.';
        }
    
        $lines = $invoices->map(function ($invoice) {
            $contractText = $invoice->contract ? " | Ugovor: {$invoice->contract->contract_number}" : '';
            $company = $invoice->company->name ?? '-';
            $buyer = $invoice->buyer->name ?? '-';
    
            return "- {$invoice->invoice_number}: {$invoice->issued_at->format('Y-m-d')}, {$invoice->total_price_to_pay} EUR | Firma: {$company} | Kupac: {$buyer}{$contractText}";
        })->join("\n");
    
        $total = round($invoices->sum(fn($invoice) => (float) $invoice->total_price_to_pay), 2);
    
        $scope = $contract ? " za ugovor {$contract->contract_number}" : '';
    
        return "Nefiskalizovane fakture{$scope} ({$invoices->count()}):\n{$lines}\n\nUkupno za fiskalizaciju: {$total} EUR";
    }
    
    private function isShowInvoiceItemsRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return $this->extractInvoiceNumber($message) !== null
            && (
                str_contains($normalizedMessage, 'stavke')
                || str_contains($normalizedMessage, 'stavka')
                || str_contains($normalizedMessage, 'items')
                || str_contains($normalizedMessage, 'proizvod')
                || str_contains($normalizedMessage, 'proizvodi')
            )
            && (
                str_contains($normalizedMessage, 'faktura')
                || str_contains($normalizedMessage, 'fakture')
                || str_contains($normalizedMessage, 'račun')
                || str_contains($normalizedMessage, 'racun')
                || str_contains($normalizedMessage, 'invoice')
            );
    }
    
    private function isShowInvoiceRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return $this->extractInvoiceNumber($message) !== null
            && (
                str_contains($normalizedMessage, 'prikazi')
                || str_contains($normalizedMessage, 'prikaži')
                || str_contains($normalizedMessage, 'vidi')
                || str_contains($normalizedMessage, 'pogledaj')
                || str_contains($normalizedMessage, 'show')
                || str_contains($normalizedMessage, 'faktura')
                || str_contains($normalizedMessage, 'fakture')
                || str_contains($normalizedMessage, 'račun')
                || str_contains($normalizedMessage, 'racun')
                || str_contains($normalizedMessage, 'invoice')
            )
            && !$this->isCreateInvoiceRequest($message);
    }
    
    private function handleShowInvoiceRequest(string $message): string
    {
        $invoice = $this->findInvoiceForChat($message);
        if (!$invoice) {
            return 'Ne mogu da pronađem tu fakturu. Možeš napisati npr. „prikaži fakturu 1/2026/001/enu” ili dio broja fakture.';
        }
    
        $status = $invoice->fic ? "fiskalizovana (FIC: {$invoice->fic})" : 'nije fiskalizovana';
        $contractText = $invoice->contract ? $invoice->contract->contract_number : '-';
        $this->rememberChatContext($invoice->contract, $invoice);
    
        return "Faktura {$invoice->invoice_number}\nUgovor: {$contractText}\nFirma: {$invoice->company->name}\nKupac: {$invoice->buyer->name}\nDatum: {$invoice->issued_at->format('Y-m-d')}\nPeriod: {$invoice->tax_period}\nNačin plaćanja: {$invoice->payment_method_type->value}\nStatus: {$status}\nBroj stavki: {$invoice->items->count()}\nUkupno bez PDV-a: {$invoice->total_price_without_vat} EUR\nPDV: {$invoice->total_vat_amount} EUR\nUkupno za plaćanje: {$invoice->total_price_to_pay} EUR";
    }
    
    private function handleShowInvoiceItemsRequest(string $message): string
    {
        $invoice = $this->findInvoiceForChat($message);
        if (!$invoice) {
            return 'Ne mogu da pronađem tu fakturu. Možeš napisati npr. „prikaži stavke fakture 1/2026/001/enu” ili dio broja fakture.';
        }
    
        if ($invoice->items->isEmpty()) {
            return "Faktura {$invoice->invoice_number} nema stavke.";
        }
    
        $this->rememberChatContext($invoice->contract, $invoice);
    
        $lines = $invoice->items->map(function ($item) {
            $vatRate = $item->vatRate->percentage ?? 0;
            $base = round((float) $item->quantity * (float) $item->unit_price, 2);
            $vat = round($base * ((float) $vatRate / 100), 2);
            $total = round($base + $vat, 2);
    
            return "- {$item->product->name}: {$item->quantity} x {$item->unit_price} EUR, PDV {$vatRate}%, ukupno {$total} EUR";
        })->join("\n");
    
        return "Stavke fakture {$invoice->invoice_number}:\n{$lines}\n\nUkupno bez PDV-a: {$invoice->total_price_without_vat} EUR\nPDV: {$invoice->total_vat_amount} EUR\nUkupno za plaćanje: {$invoice->total_price_to_pay} EUR";
    }
    
    private function handleDownloadInvoicePdfRequest(string $message): array
    {
        $invoice = $this->findInvoiceForPdfRequest($message);
    
        if (!$invoice) {
            return [
                'response' => 'Ne mogu da pronađem fakturu za PDF. Možeš napisati npr. „daj mi PDF zadnje fakture za ugovor CTR-001” ili „preuzmi fakturu 1/2026/001/enu”.',
            ];
        }
    
        $downloadUrl = route('invoice.pdf', ['id' => $invoice->id]);
        $contractText = $invoice->contract ? " za ugovor {$invoice->contract->contract_number}" : '';
        $this->rememberChatContext($invoice->contract, $invoice);
    
        return [
            'response' => "Spreman je PDF fakture {$invoice->invoice_number}{$contractText}.\nKlikni na dugme za preuzimanje.",
            'download_url' => $downloadUrl,
            'download_label' => 'Preuzmi PDF',
        ];
    }
    
    private function handleSendInvoiceEmailRequest(Request $request, string $message): array|string
    {
        $email = $this->extractEmailAddress($message);
        if (!$email) {
            return 'Mogu da pošaljem fakturu na mejl, samo mi treba validna email adresa. Npr. „pošalji zadnju fakturu za ugovor CTR-001 na mejl test@example.com”.';
        }
    
        $invoice = $this->findInvoiceForPdfRequest($message);
        if (!$invoice) {
            return 'Ne mogu da pronađem fakturu za slanje. Možeš napisati npr. „pošalji zadnju fakturu za ugovor CTR-001 na mejl test@example.com” ili „pošalji fakturu za april za CTR-001 na mejl test@example.com”.';
        }
    
        $invoice->loadMissing(['company', 'buyer', 'contract']);
        $this->rememberChatContext($invoice->contract, $invoice);
        $filename = 'faktura-' . preg_replace('/[^A-Za-z0-9\-]/', '-', $invoice->invoice_number) . '.pdf';
        $contractText = $invoice->contract ? $invoice->contract->contract_number : '-';
    
        session()->put('pending_chat_action', [
            'type' => 'send_invoice_email',
            'invoice_id' => $invoice->id,
            'email' => $email,
            'message' => $message,
        ]);
    
        return [
            'response' => "Pregled slanja fakture prije slanja:\nFaktura: {$invoice->invoice_number}\nUgovor: {$contractText}\nFirma: {$invoice->company->name}\nKupac: {$invoice->buyer->name}\nDatum: {$invoice->issued_at->format('Y-m-d')}\nUkupno za plaćanje: {$invoice->total_price_to_pay} EUR\nPrimaoc: {$email}\nPDF prilog: {$filename}",
            'quick_actions' => $this->confirmationQuickActions(),
        ];
    }
    
    private function sendInvoiceEmail(int $invoiceId, string $email, string $requestId): string
    {
        $invoice = \App\Models\Invoice::with([
            'items.product.vatRate',
            'company',
            'buyer',
            'user',
            'contract',
        ])->find($invoiceId);
    
        if (!$invoice) {
            return 'Ne mogu da pronađem fakturu za slanje.';
        }
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Email adresa za slanje nije validna.';
        }
    
        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
            $filename = 'faktura-' . preg_replace('/[^A-Za-z0-9\-]/', '-', $invoice->invoice_number) . '.pdf';
            $subject = "Faktura {$invoice->invoice_number}";
            $body = "Poštovani,\n\nU prilogu se nalazi faktura {$invoice->invoice_number}.\n\nSrdačno,\nFiscalizationME";
    
            \Illuminate\Support\Facades\Mail::raw($body, function ($mail) use ($email, $subject, $pdf, $filename) {
                $mail->to($email)
                    ->subject($subject)
                    ->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
            });
    
            \Log::info('LLM action completed', [
                'request_id' => $requestId,
                'action' => 'send_invoice_email',
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'email' => $email,
                'mailer' => config('mail.default'),
            ]);
    
            $contractText = $invoice->contract ? " za ugovor {$invoice->contract->contract_number}" : '';
            $mailerNote = config('mail.default') === 'log' ? "\nNapomena: MAIL_MAILER je trenutno log, pa je email upisan u log umjesto stvarnog slanja." : '';
            $this->rememberChatContext($invoice->contract, $invoice);
    
            return "Poslao sam fakturu {$invoice->invoice_number}{$contractText} na {$email}.{$mailerNote}";
        } catch (\Throwable $e) {
            \Log::error('LLM action failed', [
                'request_id' => $requestId,
                'action' => 'send_invoice_email',
                'invoice_id' => $invoice->id ?? null,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
    
            return 'Nisam uspio da pošaljem fakturu na mejl: ' . $e->getMessage();
        }
    }
    
    private function extractEmailAddress(string $message): ?string
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $message, $matches) !== 1) {
            return null;
        }
    
        $email = strtolower(trim($matches[0], " \t\n\r\0\x0B.,;:"));
    
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
    
    private function findInvoiceForPdfRequest(string $message)
    {
        $invoice = $this->findInvoiceForChat($message);
        if ($invoice) {
            return $invoice;
        }
    
        $contract = $this->findContractForChat($message);
        if (!$contract) {
            return $this->lastContextInvoice();
        }
    
        $period = $this->extractInvoicePeriod($message);
    
        return \App\Models\Invoice::with([
            'company',
            'buyer',
            'contract',
            'user',
            'items.product.vatRate',
            'items.vatRate',
        ])
            ->where('contract_id', $contract->id)
            ->when($period, fn($query) => $query
                ->whereMonth('issued_at', $period['month'])
                ->whereYear('issued_at', $period['year']))
            ->when(
                $this->isFirstInvoiceQuestion($message),
                fn($query) => $query->orderBy('issued_at')->orderBy('id'),
                fn($query) => $query->orderByDesc('issued_at')->orderByDesc('id')
            )
            ->first();
    }
    
    private function isFirstInvoiceQuestion(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return str_contains($normalizedMessage, 'prva')
            || str_contains($normalizedMessage, 'prvu')
            || str_contains($normalizedMessage, 'prve')
            || str_contains($normalizedMessage, 'najstarija')
            || str_contains($normalizedMessage, 'najstariju')
            || str_contains($normalizedMessage, 'oldest')
            || str_contains($normalizedMessage, 'first');
    }
    
    private function findInvoiceForChat(string $message)
    {
        $invoiceNumber = $this->extractInvoiceNumber($message);
        if ($invoiceNumber === null) {
            return $this->referencesPreviousInvoice($message) ? $this->lastContextInvoice() : null;
        }
    
        return \App\Models\Invoice::with([
            'company',
            'buyer',
            'contract',
            'items.product.vatRate',
            'items.vatRate',
        ])
            ->where('invoice_number', $invoiceNumber)
            ->orWhere('invoice_number', 'like', '%' . $invoiceNumber . '%')
            ->orderBy('issued_at', 'desc')
            ->first();
    }
    
    private function extractInvoiceNumber(string $message): ?string
    {
        if (preg_match('/\b\d+\/\d{4}\/[A-Za-z0-9_-]+\/[A-Za-z0-9_-]+\b/', $message, $matches) === 1) {
            return $matches[0];
        }
    
        if (preg_match('/\b(?:faktura|fakture|račun|racun|invoice)\s+([A-Za-z0-9\/_-]+)\b/iu', $message, $matches) === 1) {
            $candidate = trim($matches[1], " \t\n\r\0\x0B.,;:");
    
            return in_array(mb_strtolower($candidate), ['za', 'od', 'iz', 'ugovor', 'contract'], true) ? null : $candidate;
        }
    
        return null;
    }
    
    private function isShowContractItemsRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return $this->extractContractNumber($message) !== null
            && (
                str_contains($normalizedMessage, 'stavke')
                || str_contains($normalizedMessage, 'stavka')
                || str_contains($normalizedMessage, 'items')
                || str_contains($normalizedMessage, 'proizvod')
                || str_contains($normalizedMessage, 'proizvodi')
                || str_contains($normalizedMessage, 'usluge')
            )
            && !$this->isCreateContractRequest($message)
            && !$this->isCreateInvoiceRequest($message);
    }
    
    private function isShowContractInvoicesRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return $this->extractContractNumber($message) !== null
            && (
                str_contains($normalizedMessage, 'faktura')
                || str_contains($normalizedMessage, 'fakture')
                || str_contains($normalizedMessage, 'fakturis')
                || str_contains($normalizedMessage, 'invoice')
                || str_contains($normalizedMessage, 'invoices')
                || str_contains($normalizedMessage, 'zadnja')
                || str_contains($normalizedMessage, 'poslednja')
                || str_contains($normalizedMessage, 'last')
            )
            && !$this->isCreateInvoiceRequest($message);
    }
    
    private function isShowContractRequest(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return $this->extractContractNumber($message) !== null
            && (
                str_contains($normalizedMessage, 'prikazi')
                || str_contains($normalizedMessage, 'prikaži')
                || str_contains($normalizedMessage, 'vidi')
                || str_contains($normalizedMessage, 'pogledaj')
                || str_contains($normalizedMessage, 'show')
                || str_contains($normalizedMessage, 'what about')
                || str_contains($normalizedMessage, 'sta je')
                || str_contains($normalizedMessage, 'šta je')
                || str_contains($normalizedMessage, 'ugovor')
                || str_contains($normalizedMessage, 'contract')
            )
            && !$this->isCreateContractRequest($message)
            && !$this->isCreateInvoiceRequest($message);
    }
    
    private function handleShowContractInvoicesRequest(string $message): string
    {
        $contract = $this->findContractForChat($message);
        if (!$contract) {
            return 'Ne mogu da pronađem taj ugovor. Možeš napisati npr. „prikaži fakture za ctr 012” ili „da li je ugovor 12 fakturisan za april”.';
        }
        $this->rememberChatContext($contract);
    
        $date = $this->extractInvoiceDate($message);
        $period = $date ? null : $this->extractInvoicePeriod($message);
        $invoices = $contract->invoices->sortByDesc('issued_at');
    
        if ($date) {
            $invoices = $invoices->filter(fn($invoice) => $invoice->issued_at
                && $invoice->issued_at->toDateString() === $date->toDateString());
        }
    
        if ($period) {
            $invoices = $invoices->filter(fn($invoice) => $invoice->issued_at
                && (int) $invoice->issued_at->format('m') === $period['month']
                && (int) $invoice->issued_at->format('Y') === $period['year']);
        }
    
        if ($invoices->isEmpty()) {
            $periodText = $date
                ? " za datum {$date->toDateString()}"
                : ($period ? " za period " . str_pad((string) $period['month'], 2, '0', STR_PAD_LEFT) . "/{$period['year']}" : '');
    
            return "Ugovor {$contract->contract_number} nema fakture{$periodText}.";
        }
    
        if ($date || ($period && $invoices->count() === 1)) {
            $this->rememberChatContext($contract, $invoices->first());
            return $this->formatInvoiceForContractResponse($contract, $invoices->first(), $date ? "za datum {$date->toDateString()}" : "za " . str_pad((string) $period['month'], 2, '0', STR_PAD_LEFT) . "/{$period['year']}");
        }
    
        if ($this->isLastInvoiceQuestion($message)) {
            $this->rememberChatContext($contract, $invoices->first());
            return $this->formatInvoiceForContractResponse($contract, $invoices->first(), 'zadnja');
        }
    
        $lines = $invoices->map(function ($invoice) {
            $status = $invoice->fic ? 'fiskalizovana' : 'nije fiskalizovana';
    
            return "- {$invoice->invoice_number}: {$invoice->issued_at->format('Y-m-d')}, {$invoice->total_price_to_pay} EUR, {$status}";
        })->join("\n");
    
        $total = round($invoices->sum(fn($invoice) => (float) $invoice->total_price_to_pay), 2);
        $periodText = $period ? " za " . str_pad((string) $period['month'], 2, '0', STR_PAD_LEFT) . "/{$period['year']}" : '';
    
        return "Fakture ugovora {$contract->contract_number}{$periodText}:\n{$lines}\n\nBroj faktura: {$invoices->count()}\nUkupno fakturisano: {$total} EUR";
    }
    
    private function formatInvoiceForContractResponse($contract, $invoice, string $label): string
    {
        $status = $invoice->fic ? 'fiskalizovana' : 'nije fiskalizovana';
    
        return "Faktura {$label} za ugovor {$contract->contract_number}:\n- Broj fakture: {$invoice->invoice_number}\n- Datum: {$invoice->issued_at->format('Y-m-d')}\n- Period: {$invoice->tax_period}\n- Ukupno bez PDV-a: {$invoice->total_price_without_vat} EUR\n- PDV: {$invoice->total_vat_amount} EUR\n- Ukupno za plaćanje: {$invoice->total_price_to_pay} EUR\n- Status: {$status}";
    }
    
    private function isLastInvoiceQuestion(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return str_contains($normalizedMessage, 'zadnja')
            || str_contains($normalizedMessage, 'poslednja')
            || str_contains($normalizedMessage, 'posljednja')
            || str_contains($normalizedMessage, 'last')
            || str_contains($normalizedMessage, 'najnovija');
    }
    
    private function extractInvoicePeriod(string $message): ?array
    {
        $normalizedMessage = mb_strtolower($message);
        $year = preg_match('/\b(20\d{2})\b/', $message, $yearMatches) === 1 ? (int) $yearMatches[1] : now()->year;
    
        if (preg_match('/\b(\d{1,2})\/(\d{4})\b/', $message, $matches) === 1) {
            return ['month' => (int) $matches[1], 'year' => (int) $matches[2]];
        }
    
        if (preg_match('/\b(\d{4})-(\d{1,2})(?:-\d{1,2})?\b/', $message, $matches) === 1) {
            return ['month' => (int) $matches[2], 'year' => (int) $matches[1]];
        }
    
        if (preg_match('/\b(?:mjesec|mesec)\s*(\d{1,2})\b/u', $normalizedMessage, $matches) === 1
            || preg_match('/\b(\d{1,2})\.?\s*(?:mjesec|mesec)\b/u', $normalizedMessage, $matches) === 1
        ) {
            $month = (int) $matches[1];
            if ($month >= 1 && $month <= 12) {
                return ['month' => $month, 'year' => $year];
            }
        }
    
        $ordinalMonths = [
            'prvi' => 1, 'prvom' => 1,
            'drugi' => 2, 'drugom' => 2,
            'treci' => 3, 'treći' => 3, 'trecem' => 3, 'trećem' => 3,
            'cetvrti' => 4, 'četvrti' => 4, 'cetvrtom' => 4, 'četvrtom' => 4,
            'peti' => 5, 'petom' => 5,
            'sesti' => 6, 'šesti' => 6, 'sestom' => 6, 'šestom' => 6,
            'sedmi' => 7, 'sedmom' => 7,
            'osmi' => 8, 'osmom' => 8,
            'deveti' => 9, 'devetom' => 9,
            'deseti' => 10, 'desetom' => 10,
            'jedanaesti' => 11, 'jedanaestom' => 11,
            'dvanaesti' => 12, 'dvanaestom' => 12,
        ];
    
        foreach ($ordinalMonths as $word => $month) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\s+(?:mjesec|mesec)\b/u', $normalizedMessage) === 1) {
                return ['month' => $month, 'year' => $year];
            }
        }
    
        $months = [
            'januar' => 1, 'january' => 1,
            'februar' => 2, 'february' => 2,
            'mart' => 3, 'march' => 3,
            'april' => 4,
            'maj' => 5, 'may' => 5,
            'jun' => 6, 'june' => 6,
            'jul' => 7, 'july' => 7,
            'avgust' => 8, 'august' => 8,
            'septembar' => 9, 'september' => 9,
            'oktobar' => 10, 'october' => 10,
            'novembar' => 11, 'november' => 11,
            'decembar' => 12, 'december' => 12,
        ];
    
        foreach ($months as $name => $month) {
            if (str_contains($normalizedMessage, $name)) {
                return ['month' => $month, 'year' => $year];
            }
        }
    
        return null;
    }
    
    private function extractInvoiceDate(string $message): ?\Carbon\Carbon
    {
        if (preg_match('/\b(20\d{2})-(\d{1,2})-(\d{1,2})\b/', $message, $matches) === 1) {
            return \Carbon\Carbon::createFromDate((int) $matches[1], (int) $matches[2], (int) $matches[3])->startOfDay();
        }
    
        if (preg_match('/\b(\d{1,2})[.\-\/](\d{1,2})[.\-\/](20\d{2})\b/', $message, $matches) === 1) {
            return \Carbon\Carbon::createFromDate((int) $matches[3], (int) $matches[2], (int) $matches[1])->startOfDay();
        }
    
        return null;
    }
    
    private function handleShowContractItemsRequest(string $message): string
    {
        $contract = $this->findContractForChat($message);
        if (!$contract) {
            return 'Ne mogu da pronađem taj ugovor. Možeš napisati npr. „prikaži stavke za ctr 012” ili „stavke ugovora 12”.';
        }
        $this->rememberChatContext($contract);
    
        if ($contract->items->isEmpty()) {
            return "Ugovor {$contract->contract_number} nema stavke.";
        }
    
        [$totalWithoutVat, $totalVat, $totalWithVat] = $this->calculateContractTotals($contract);
        $items = $contract->items->map(function ($item) {
            $vatRate = $item->vatRate->percentage ?? 0;
            $base = round((float) $item->quantity * (float) $item->unit_price, 2);
            $vat = round($base * ((float) $vatRate / 100), 2);
            $total = round($base + $vat, 2);
    
            return "- {$item->product->name}: {$item->quantity} x {$item->unit_price} EUR, PDV {$vatRate}%, ukupno {$total} EUR";
        })->join("\n");
    
        return "Stavke ugovora {$contract->contract_number}:\n{$items}\n\nUkupno bez PDV-a: {$totalWithoutVat} EUR\nPDV: {$totalVat} EUR\nUkupno za plaćanje: {$totalWithVat} EUR";
    }
    
    private function handleShowContractRequest(string $message): string
    {
        $contract = $this->findContractForChat($message);
        if (!$contract) {
            return 'Ne mogu da pronađem taj ugovor. Možeš napisati npr. „prikaži ctr 012”, „vidi ugovor 12” ili „what about ctr012”.';
        }
        $this->rememberChatContext($contract);
    
        [$totalWithoutVat, $totalVat, $totalWithVat] = $this->calculateContractTotals($contract);
        $invoiceCount = $contract->invoices->count();
        $lastInvoice = $contract->invoices->sortByDesc('issued_at')->first();
        $lastInvoiceText = $lastInvoice
            ? "{$lastInvoice->invoice_number} ({$lastInvoice->issued_at->format('Y-m-d')}, {$lastInvoice->total_price_to_pay} EUR)"
            : 'nema faktura';
        if ($lastInvoice) {
            $this->rememberChatContext($contract, $lastInvoice);
        }
    
        return "Ugovor {$contract->contract_number}\nFirma: {$contract->company->name}\nKupac: {$contract->buyer->name}\nStatus: {$contract->status}\nPeriod: {$contract->start_date->format('Y-m-d')} - {$contract->end_date->format('Y-m-d')}\nKreiranje fakture: {$contract->billing_frequency}\nDan izdavanja: {$contract->issue_day}\nNačin plaćanja: {$contract->default_payment_method}\nBroj stavki: {$contract->items->count()}\nBroj faktura: {$invoiceCount}\nZadnja faktura: {$lastInvoiceText}\n\nUkupno bez PDV-a: {$totalWithoutVat} EUR\nPDV: {$totalVat} EUR\nUkupno za plaćanje: {$totalWithVat} EUR";
    }
    
    private function findContractForChat(string $message)
    {
        $contractNumber = $this->extractContractNumber($message);
        if ($contractNumber === null) {
            if ($this->referencesPreviousContract($message)) {
                return $this->lastContextContract();
            }
    
            $entities = session('current_ai_entities', []);
            $partyName = $entities['customer_name'] ?? $entities['company_name'] ?? null;
    
            return is_string($partyName) ? $this->findContractByPartyName($partyName) : null;
        }
    
        return \App\Models\Contract::with([
            'company',
            'buyer',
            'items.product.vatRate',
            'items.vatRate',
            'invoices',
        ])->where('contract_number', $contractNumber)->first();
    }
    
    private function findContractByPartyName(string $partyName)
    {
        $partyName = trim($partyName);
        if ($partyName === '') {
            return null;
        }
    
        $contracts = \App\Models\Contract::with([
            'company',
            'buyer',
            'items.product.vatRate',
            'items.vatRate',
            'invoices',
        ])->latest('id')->take(100)->get();
    
        $normalizedNeedle = $this->normalizeSearchText($partyName);
    
        return $contracts
            ->sortByDesc(function ($contract) use ($normalizedNeedle) {
                $companyScore = $this->fuzzyNameScore($normalizedNeedle, $contract->company->name ?? '');
                $buyerScore = $this->fuzzyNameScore($normalizedNeedle, $contract->buyer->name ?? '');
    
                return max($companyScore, $buyerScore);
            })
            ->first(function ($contract) use ($normalizedNeedle) {
                $companyScore = $this->fuzzyNameScore($normalizedNeedle, $contract->company->name ?? '');
                $buyerScore = $this->fuzzyNameScore($normalizedNeedle, $contract->buyer->name ?? '');
    
                return max($companyScore, $buyerScore) >= 70;
            });
    }
    
    private function normalizeSearchText(string $text): string
    {
        $text = mb_strtolower($text);
        $replacements = ['č' => 'c', 'ć' => 'c', 'š' => 's', 'đ' => 'dj', 'ž' => 'z'];
        $text = strtr($text, $replacements);
    
        return preg_replace('/[^a-z0-9]+/u', '', $text) ?: '';
    }
    
    private function fuzzyNameScore(string $needle, string $candidate): int
    {
        $candidate = $this->normalizeSearchText($candidate);
        if ($needle === '' || $candidate === '') {
            return 0;
        }
    
        if (str_contains($candidate, $needle) || str_contains($needle, $candidate)) {
            return 100;
        }
    
        $maxLength = max(strlen($needle), strlen($candidate));
        if ($maxLength === 0) {
            return 0;
        }
    
        $distance = levenshtein($needle, $candidate);
    
        return max(0, (int) round((1 - ($distance / $maxLength)) * 100));
    }
    
    private function calculateContractTotals($contract): array
    {
        $totalWithoutVat = 0;
        $totalVat = 0;
    
        foreach ($contract->items as $item) {
            $base = round((float) $item->quantity * (float) $item->unit_price, 2);
            $vatRate = $item->vatRate->percentage ?? 0;
            $vat = round($base * ((float) $vatRate / 100), 2);
            $totalWithoutVat += $base;
            $totalVat += $vat;
        }
    
        $totalWithoutVat = round($totalWithoutVat, 2);
        $totalVat = round($totalVat, 2);
    
        return [$totalWithoutVat, $totalVat, round($totalWithoutVat + $totalVat, 2)];
    }
}
