<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;

trait ChatInvoiceCreationTrait
{
    private function handleCreateInvoiceRequest(Request $request, string $message, string $provider, string $requestId): array|string
    {
        $existingDraft = $this->getDraftPayload('create_invoice');
        $contextMessage = $message;
        if (!empty($existingDraft['contract_number']) && $this->extractContractNumber($message) === null) {
            $contextMessage .= ' ugovor ' . $existingDraft['contract_number'];
        }
    
        $contextJson = $this->buildInvoiceCreationContextJson($contextMessage);
        $extracted = $this->extractInvoicePayloadWithAi($message, $contextJson, $provider, $requestId);
    
        if (isset($extracted['error'])) {
            return $extracted['error'];
        }
    
        $extracted = $this->mergeInvoiceDraftPayload($existingDraft, $extracted);
        $contractNumber = $this->normalizeExtractedContractNumber($extracted['contract_number'] ?? null);
        if ($contractNumber === null) {
            $this->storeDraftAction($request, 'create_invoice', $extracted, $message);
    
            return $this->buildInvoiceDraftQuestion($extracted, ['broj ugovora']);
        }
    
        $contract = \App\Models\Contract::with(['items.product.vatRate', 'company.users', 'buyer'])
            ->where('contract_number', $contractNumber)
            ->first();
    
        if (!$contract) {
            return "Ne mogu da pronađem ugovor {$contractNumber}.";
        }
    
        if ($contract->status !== 'active') {
            return "Faktura nije kreirana jer ugovor {$contractNumber} nije aktivan. Trenutni status: {$contract->status}.";
        }
    
        if ($contract->items->isEmpty()) {
            return "Faktura nije kreirana jer ugovor {$contractNumber} nema stavke.";
        }
    
        $issueDate = $this->resolveInvoiceIssueDateFromPayload($extracted);
    
        if ($contract->start_date && $issueDate->lt($contract->start_date)) {
            return "Faktura nije kreirana jer je datum {$issueDate->toDateString()} prije početka ugovora {$contract->start_date->toDateString()}.";
        }
    
        if ($contract->end_date && $issueDate->gt($contract->end_date)) {
            return "Faktura nije kreirana jer je datum {$issueDate->toDateString()} poslije završetka ugovora {$contract->end_date->toDateString()}.";
        }
    
        if ($this->invoiceExistsForContractPeriod($contract, $issueDate)) {
            return "Faktura za ugovor {$contractNumber} već postoji za period {$issueDate->format('m/Y')}.";
        }
    
        $preview = $this->buildInvoicePreviewFromContract($contract, $issueDate);
    
        session()->put('pending_chat_action', [
            'type' => 'create_invoice',
            'contract_number' => $contractNumber,
            'issue_date' => $issueDate->toDateString(),
            'message' => $message,
        ]);
        session()->forget('chat_draft_action');
    
        return [
            'response' => $preview,
            'quick_actions' => $this->confirmationQuickActions(),
        ];
    }
    
    private function getDraftPayload(string $type): array
    {
        $draft = session('chat_draft_action');
    
        if (!is_array($draft) || ($draft['type'] ?? null) !== $type) {
            return [];
        }
    
        return is_array($draft['payload'] ?? null) ? $draft['payload'] : [];
    }
    
    private function draftSourceMessages(string $type, string $message): array
    {
        $draft = session('chat_draft_action');
        $messages = [];
    
        if (is_array($draft) && ($draft['type'] ?? null) === $type && is_array($draft['messages'] ?? null)) {
            $messages = $draft['messages'];
        }
    
        $messages[] = $message;
    
        return array_values(array_filter(array_slice($messages, -8), fn($item) => is_string($item) && trim($item) !== ''));
    }
    
    private function storeDraftAction(Request $request, string $type, array $payload, string $message): void
    {
        $messages = $this->draftSourceMessages($type, $message);
    
        session()->put('chat_draft_action', [
            'type' => $type,
            'payload' => $payload,
            'messages' => $messages,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }
    
    private function mergeInvoiceDraftPayload(array $draft, array $current): array
    {
        return [
            'contract_number' => $this->firstFilledValue($current['contract_number'] ?? null, $draft['contract_number'] ?? null),
            'issue_date' => $this->firstFilledValue($current['issue_date'] ?? null, $draft['issue_date'] ?? null),
        ];
    }
    
    private function buildInvoiceDraftQuestion(array $payload, array $missing): string
    {
        $summary = [];
    
        if (!empty($payload['contract_number'])) {
            $summary[] = "Ugovor: {$payload['contract_number']}";
        }
    
        if (!empty($payload['issue_date'])) {
            $summary[] = "Datum/period fakture: {$payload['issue_date']}";
        }
    
        $summaryText = $summary ? "\n\nDo sada imam:\n- " . implode("\n- ", $summary) : '';
    
        return "Mogu da pripremim fakturu, ali nedostaje:\n- " . implode("\n- ", $missing) . $summaryText . "\n\nDopiši podatak prirodno, npr. „za ugovor CTR-001” ili „za april 2026”.";
    }
    
    private function buildInvoiceCreationContextJson(string $message): string
    {
        $contractNumber = $this->extractContractNumber($message);
        $query = \App\Models\Contract::with(['company', 'buyer'])->orderByDesc('id');
    
        if ($contractNumber !== null) {
            $query->where('contract_number', $contractNumber);
        } else {
            $query->take(25);
        }
    
        $contracts = $query->get()
            ->map(fn($contract) => [
                'contract_number' => $contract->contract_number,
                'status' => $contract->status,
                'start_date' => $contract->start_date ? $contract->start_date->toDateString() : null,
                'end_date' => $contract->end_date ? $contract->end_date->toDateString() : null,
                'billing_frequency' => $contract->billing_frequency,
                'company_name' => $contract->company->name ?? null,
                'buyer_name' => $contract->buyer->name ?? null,
            ])
            ->values()
            ->all();
    
        return json_encode([
            'current_date' => now()->toDateString(),
            'contracts' => $contracts,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private function extractInvoicePayloadWithAi(string $message, string $contextJson, string $provider, string $requestId): array
    {
        $systemPrompt = $provider === 'apple'
            ? "Extract data for preparing an invoice in the FiscalizationME app.
    Return only valid JSON, without markdown and without explanation.
    Do not create an invoice and do not answer the user.
    From the user message, detect the contract and optional invoice date or period.
    The user's text can contain local terms such as ugovor=contract, faktura=invoice, fakturisi=create invoice, mjesec=month.
    
    CONTEXT:
    {$contextJson}
    
    Return JSON:
    {\"contract_number\": string|null, \"issue_date\": \"YYYY-MM-DD\"|null}"
            : "Ti izvlačiš podatke za pripremu fakture u FiscalizationME aplikaciji.
    Vrati samo validan JSON bez markdowna i bez objašnjenja.
    Ne kreiraš fakturu i ne odgovaraš korisniku.
    Iz korisničke poruke prepoznaj ugovor i opcioni datum/period fakture.
    Koristi ugovore iz konteksta za normalizaciju broja ugovora. Ako korisnik napiše 'ctr 12', 'ctr012', 'ugovor 12' ili slično, vrati postojeći contract_number iz konteksta ako postoji.
    Ako datum ili period nije naveden, issue_date je null.
    Ako korisnik navede mjesec i godinu, issue_date neka bude prvi dan tog mjeseca.
    Ako korisnik navede samo relativni period (npr. ovaj mjesec, prošli mjesec), koristi current_date iz konteksta.
    
    KONTEKST:
    {$contextJson}
    
    Vrati JSON oblika:
    {\"contract_number\": string|null, \"issue_date\": \"YYYY-MM-DD\"|null}";
    
        $content = match ($provider) {
            'apple' => $this->callAppleJsonExtractor($message, $systemPrompt, $requestId, 'apple_create_invoice_extract'),
            'gemini' => $this->callGemini($message, $systemPrompt, $requestId, 'gemini_create_invoice_extract'),
            default => $this->callOllama($message, [], $systemPrompt, $requestId, 'ollama_create_invoice_extract'),
        };
    
        if (str_starts_with($content, 'Greška') || str_contains($content, 'unsupportedLanguageOrLocale')) {
            return ['error' => $content];
        }
    
        return $this->decodeJsonPayload(
            $content,
            $requestId,
            $provider,
            $provider . '_create_invoice_extract',
            'Model nije vratio validan JSON za pripremu fakture. Pokušaj prirodno navesti ugovor i period, npr. „napravi fakturu za ugovor CTR-012 za april 2026”.'
        );
    }
    
    private function normalizeExtractedContractNumber($contractNumber): ?string
    {
        if (!is_string($contractNumber) || trim($contractNumber) === '') {
            return null;
        }
    
        $resolved = $this->extractContractNumber($contractNumber);
        if ($resolved !== null) {
            return $resolved;
        }
    
        return trim($contractNumber);
    }
    
    private function resolveInvoiceIssueDateFromPayload(array $payload): \Carbon\Carbon
    {
        $issueDate = $payload['issue_date'] ?? null;
    
        if (is_string($issueDate) && trim($issueDate) !== '' && strtotime($issueDate)) {
            return \Carbon\Carbon::parse($issueDate)->startOfDay();
        }
    
        return \Carbon\Carbon::today();
    }
    
    private function buildInvoicePreviewFromContract($contract, \Carbon\Carbon $issueDate): string
    {
        $totalWithoutVat = 0;
        $totalVat = 0;
        $items = [];
    
        foreach ($contract->items as $item) {
            $base = round((float) $item->quantity * (float) $item->unit_price, 2);
            $vatRate = $item->vatRate->percentage ?? 0;
            $vat = round($base * ((float) $vatRate / 100), 2);
            $totalWithoutVat += $base;
            $totalVat += $vat;
            $items[] = "- {$item->product->name}: {$item->quantity} x {$item->unit_price} EUR, PDV {$vatRate}%";
        }
    
        $totalWithoutVat = round($totalWithoutVat, 2);
        $totalVat = round($totalVat, 2);
        $totalWithVat = round($totalWithoutVat + $totalVat, 2);
    
        return "Pregled fakture prije kreiranja:\nUgovor: {$contract->contract_number}\nDatum fakture: {$issueDate->toDateString()}\nFirma: {$contract->company->name}\nKupac: {$contract->buyer->name}\nUkupno bez PDV-a: {$totalWithoutVat} EUR\nPDV: {$totalVat} EUR\nStavke:\n" . implode("\n", $items) . "\n\nUkupno za plaćanje: {$totalWithVat} EUR";
    }
    
    private function extractInvoiceIssueDate(string $message): \Carbon\Carbon
    {
        if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/', $message, $matches) === 1) {
            return \Carbon\Carbon::createFromDate((int) $matches[1], (int) $matches[2], (int) $matches[3])->startOfDay();
        }
    
        if (preg_match('/\b(\d{1,2})-(\d{1,2})-(\d{4})\b/', $message, $matches) === 1) {
            return \Carbon\Carbon::createFromDate((int) $matches[3], (int) $matches[2], (int) $matches[1])->startOfDay();
        }
    
        if (preg_match('/\b(\d{1,2})\/(\d{4})\b/', $message, $matches) === 1) {
            return \Carbon\Carbon::createFromDate((int) $matches[2], (int) $matches[1], 1)->startOfDay();
        }
    
        return \Carbon\Carbon::today();
    }
    
    private function invoiceExistsForContractPeriod($contract, \Carbon\Carbon $issueDate): bool
    {
        $query = \App\Models\Invoice::where('contract_id', $contract->id);
    
        return match ($contract->billing_frequency) {
            'quarterly' => $query->where('issued_at', '>=', $issueDate->copy()->subMonths(3))->exists(),
            'yearly' => $query->whereYear('issued_at', $issueDate->year)->exists(),
            default => $query->whereMonth('issued_at', $issueDate->month)
                ->whereYear('issued_at', $issueDate->year)
                ->exists(),
        };
    }
    
    private function createInvoiceFromContract($contract, \Carbon\Carbon $issueDate, string $requestId)
    {
        $invoice = \Illuminate\Support\Facades\DB::transaction(function () use ($contract, $issueDate) {
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
            $totalWithVat = round($totalWithoutVat + $totalVat, 2);
            $orderNumber = \App\Models\Invoice::whereYear('issued_at', $issueDate->year)->count() + 1;
            $buCode = strtolower($contract->company->business_unit_code ?? '');
            $enuCode = strtolower($contract->company->enu_code ?? '');
            $invoiceNumber = "{$orderNumber}/{$issueDate->year}/{$buCode}/{$enuCode}";
            $userId = $contract->company->users()->where('is_active', true)->first()->id
                ?? \App\Models\User::query()->value('id')
                ?? 1;
    
            $invoice = \App\Models\Invoice::create([
                'invoice_number' => $invoiceNumber,
                'order_number' => $orderNumber,
                'invoice_type' => 'INVOICE',
                'type_of_invoice' => $contract->default_type_of_invoice ?? 'NONCASH',
                'issued_at' => $issueDate,
                'tax_period' => $issueDate->format('m/Y'),
                'total_price_without_vat' => $totalWithoutVat,
                'payment_method_type' => $contract->default_payment_method ?? 'ACCOUNT',
                'total_vat_amount' => $totalVat,
                'total_price_to_pay' => $totalWithVat,
                'company_id' => $contract->company_id,
                'buyer_id' => $contract->buyer_id,
                'user_id' => $userId,
                'contract_id' => $contract->id,
                'created_at' => now(),
            ]);
    
            foreach ($contract->items as $item) {
                \App\Models\InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'vat_rate_id' => $item->vat_rate_id,
                ]);
            }
    
            return $invoice->load('buyer', 'items.product');
        });
    
        \Log::info('LLM action completed', [
            'request_id' => $requestId,
            'action' => 'create_invoice',
            'contract_id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);
    
        return $invoice;
    }
}
