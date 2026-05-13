<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;

trait ChatPromptDataTrait
{
    private function buildCompactApplePromptDataJson(string $message): string
    {
        $normalizedMessage = mb_strtolower($message);
        $contractNumber = $this->extractContractNumber($message);
    
        if ($contractNumber !== null) {
            return $this->buildContractDetailsPromptDataJson($contractNumber);
        }
    
        if ($this->isCompanyListQuestion($normalizedMessage)) {
            $companies = \App\Models\Company::query()
                ->orderBy('name')
                ->take(80)
                ->get()
                ->map(fn($company) => [
                    'name' => $company->name,
                    'tax_id_type' => $company->tax_id_type->value ?? null,
                    'tax_id_number' => $company->tax_id_number,
                    'country' => $company->country,
                    'city' => $company->city,
                    'address' => $company->address,
                    'enu_code' => $company->enu_code,
                    'business_unit_code' => $company->business_unit_code,
                    'software_code' => $company->software_code,
                    'bank_account_number' => $company->bank_account_number,
                    'is_issuer_in_vat' => $company->is_issuer_in_vat,
                ])
                ->values()
                ->all();
    
            return json_encode(['companies' => $companies], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    
        if ($this->isGreetingOrSmallTalk($normalizedMessage)) {
            return json_encode(['note' => 'No database data is needed for this message.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    
        if ($this->isContractSummaryQuestion($normalizedMessage)) {
            return $this->buildContractSummariesPromptDataJson();
        }
    
        return $this->buildFullPromptDataJson(5);
    }
    
    private function extractContractNumber(string $message): ?string
    {
        if (preg_match('/\bCTR[\s\-]*0*(\d+)\b/i', $message, $matches) === 1) {
            return $this->resolveExistingContractNumber((int) $matches[1]);
        }
    
        if (preg_match('/\b(?:ugovor|contract)\s*#?\s*0*(\d+)\b/i', $message, $matches) === 1) {
            return $this->resolveExistingContractNumber((int) $matches[1]);
        }
    
        return null;
    }
    
    private function resolveExistingContractNumber(int $number): string
    {
        $candidates = [
            'CTR-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'CTR-' . $number,
        ];
    
        $existing = \App\Models\Contract::whereIn('contract_number', $candidates)->value('contract_number');
    
        return $existing ?: $candidates[0];
    }
    
    private function buildContractDetailsPromptDataJson(string $contractNumber): string
    {
        $contract = \App\Models\Contract::with([
            'company',
            'buyer',
            'invoices.items.product',
            'invoices.items.vatRate',
        ])
            ->where('contract_number', $contractNumber)
            ->first();
    
        return json_encode([
            'context_scope' => 'single_contract_full_details',
            'contract_number_requested' => $contractNumber,
            'contract' => $contract ? $this->formatContractForJson($contract, true) : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private function buildContractSummariesPromptDataJson(): string
    {
        $contracts = \App\Models\Contract::with(['company', 'buyer'])
            ->orderBy('contract_number')
            ->take(100)
            ->get()
            ->map(fn($contract) => $this->formatContractForJson($contract, false))
            ->values()
            ->all();
    
        return json_encode([
            'context_scope' => 'all_contracts_summary',
            'contracts_total_in_context' => count($contracts),
            'contracts' => $contracts,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private function isCompanyListQuestion(string $message): bool
    {
        return str_contains($message, 'company')
            || str_contains($message, 'companies')
            || str_contains($message, 'firm')
            || str_contains($message, 'firms')
            || str_contains($message, 'firma')
            || str_contains($message, 'firme')
            || str_contains($message, 'kompanija')
            || str_contains($message, 'kompanije');
    }
    
    private function isGreetingOrSmallTalk(string $message): bool
    {
        return in_array(trim($message), [
            'hi',
            'hello',
            'hey',
            'jesi tu',
            'zdravo',
            'cao',
            'ćao',
            'pozdrav',
        ], true);
    }
    
    private function isContractSummaryQuestion(string $message): bool
    {
        return str_contains($message, 'contract')
            || str_contains($message, 'contracts')
            || str_contains($message, 'ugovor')
            || str_contains($message, 'ugovore')
            || str_contains($message, 'ugovora')
            || str_contains($message, 'aktiv')
            || str_contains($message, 'active')
            || str_contains($message, 'inactive')
            || str_contains($message, 'neaktiv')
            || str_contains($message, 'expired')
            || str_contains($message, 'istek');
    }
    
    private function buildFullPromptDataJson(int $limit = 20): string
    {
        $contracts = \App\Models\Contract::with([
            'company',
            'buyer',
            'invoices.items.product',
            'invoices.items.vatRate',
        ])
            ->latest()->take($limit)->get()
            ->map(fn($contract) => $this->formatContractForJson($contract, true))
            ->values()
            ->all();
    
        return json_encode(['contracts' => $contracts], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private function formatContractForJson($contract, bool $includeInvoices): array
    {
        $data = [
            'contract_number' => $contract->contract_number,
            'status' => $contract->status,
            'start_date' => $contract->start_date ? $contract->start_date->toDateString() : null,
            'end_date' => $contract->end_date ? $contract->end_date->toDateString() : null,
            'billing_frequency' => $contract->billing_frequency,
            'issue_day' => $contract->issue_day,
            'default_type_of_invoice' => $contract->default_type_of_invoice,
            'default_payment_method' => $contract->default_payment_method,
            'company' => [
                'name' => $contract->company->name ?? null,
                'tax_id_type' => $contract->company->tax_id_type->value ?? null,
                'tax_id_number' => $contract->company->tax_id_number ?? null,
                'country' => $contract->company->country ?? null,
                'city' => $contract->company->city ?? null,
                'address' => $contract->company->address ?? null,
                'enu_code' => $contract->company->enu_code ?? null,
                'business_unit_code' => $contract->company->business_unit_code ?? null,
                'software_code' => $contract->company->software_code ?? null,
                'bank_account_number' => $contract->company->bank_account_number ?? null,
                'is_issuer_in_vat' => $contract->company->is_issuer_in_vat ?? null,
            ],
            'buyer' => [
                'name' => $contract->buyer->name ?? null,
                'tax_id_type' => $contract->buyer->tax_id_type->value ?? null,
                'tax_id_number' => $contract->buyer->tax_id_number ?? null,
                'country' => $contract->buyer->country ?? null,
                'city' => $contract->buyer->city ?? null,
                'address' => $contract->buyer->address ?? null,
            ],
        ];
    
        if (!$includeInvoices) {
            return $data;
        }
    
        $data['invoices'] = $contract->invoices->map(function ($invoice) {
            return [
                'invoice_number' => $invoice->invoice_number,
                'order_number' => $invoice->order_number,
                'invoice_type' => $invoice->invoice_type->value ?? null,
                'type_of_invoice' => $invoice->type_of_invoice->value ?? null,
                'issued_at' => $invoice->issued_at ? $invoice->issued_at->toDateTimeString() : null,
                'tax_period' => $invoice->tax_period,
                'payment_method_type' => $invoice->payment_method_type->value ?? null,
                'payment_deadline' => $invoice->payment_deadline,
                'total_price_without_vat' => $invoice->total_price_without_vat,
                'total_vat_amount' => $invoice->total_vat_amount,
                'total_price_to_pay' => $invoice->total_price_to_pay,
                'fic' => $invoice->fic,
                'iic' => $invoice->iic,
                'note' => $invoice->note,
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'product_code' => $item->product->code ?? null,
                        'product_name' => $item->product->name ?? null,
                        'unit' => $item->product->unit ?? null,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'vat_percentage' => $item->vatRate->percentage ?? null,
                        'vat_name' => $item->vatRate->name ?? null,
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    
        return $data;
    }
}
