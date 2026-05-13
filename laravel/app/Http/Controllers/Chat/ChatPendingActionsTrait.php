<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;

trait ChatPendingActionsTrait
{
    private function isPendingActionResponse(string $message): bool
    {
        $normalizedMessage = trim(mb_strtolower($message));
    
        return session()->has('pending_chat_action') && (
            in_array($normalizedMessage, ['potvrdi', 'potvrdjujem', 'potvrđujem', 'da', 'yes', 'confirm'], true)
            || in_array($normalizedMessage, ['ne', 'no', 'otkazi', 'otkaži', 'cancel'], true)
        );
    }
    
    private function confirmationQuickActions(): array
    {
        return [
            [
                'label' => 'Potvrdi',
                'message' => 'potvrdi',
                'style' => 'primary',
            ],
            [
                'label' => 'Otkaži',
                'message' => 'otkaži',
                'style' => 'secondary',
            ],
        ];
    }
    
    private function handlePendingActionModification(Request $request, string $message, string $provider, string $requestId): array|string
    {
        $pendingAction = session('pending_chat_action');
    
        if (!is_array($pendingAction)) {
            return 'Nema akcije koja čeka izmjenu.';
        }
    
        if (($pendingAction['type'] ?? null) === 'send_invoice_email') {
            return $this->handleSendInvoiceEmailPreviewModification($pendingAction, $message);
        }
    
        if (($pendingAction['type'] ?? null) !== 'create_contract') {
            return 'Prvo potvrdi ili otkaži trenutni preview, pa onda pošalji novu izmjenu.';
        }
    
        $payload = is_array($pendingAction['payload'] ?? null) ? $pendingAction['payload'] : [];
        if ($payload === []) {
            return 'Ne mogu da izmijenim preview jer nacrt ugovora nije validan. Pošalji zahtjev ponovo.';
        }
    
        $contextJson = $this->buildContractCreationContextJson($message);
        $updatedPayload = $this->extractContractPreviewEditWithAi($payload, $message, $contextJson, $provider, $requestId);
    
        if (isset($updatedPayload['error'])) {
            return $updatedPayload['error'];
        }
    
        $updatedPayload = $this->mergeContractDraftPayload([], $updatedPayload);
        if (empty($updatedPayload['contract_number'])) {
            $updatedPayload['contract_number'] = $payload['contract_number'] ?? $this->generateContractNumber();
        }
    
        $combinedMessage = trim(($pendingAction['message'] ?? '') . "\n" . $message);
        $validationErrors = $this->validateContractPayload($updatedPayload, $combinedMessage);
        if ($validationErrors !== []) {
            return "Ne mogu da primijenim izmjenu jer nacrt više nije validan:\n- "
                . implode("\n- ", $validationErrors)
                . "\n\nDopiši izmjenu preciznije, npr. „dodaj 1 stolicu po 80 EUR” ili „ukloni čarape”.";
        }
    
        session()->put('pending_chat_action', [
            'type' => 'create_contract',
            'payload' => $updatedPayload,
            'message' => $combinedMessage,
        ]);
    
        return [
            'response' => "Izmijenio sam preview ugovora.\n\n" . $this->buildContractPreviewFromPayload($updatedPayload, $combinedMessage),
            'quick_actions' => $this->confirmationQuickActions(),
        ];
    }
    
    private function handleSendInvoiceEmailPreviewModification(array $pendingAction, string $message): array|string
    {
        $email = $this->extractEmailAddress($message) ?: ($pendingAction['email'] ?? null);
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Ne mogu da izmijenim preview slanja jer ne vidim validnu email adresu. Napiši npr. „promijeni mejl na test@example.com”.';
        }
    
        $invoiceId = (int) ($pendingAction['invoice_id'] ?? 0);
        if ($this->messageMentionsInvoiceSelection($message)) {
            $selectedInvoice = $this->findInvoiceForPdfRequest($message);
            if ($selectedInvoice) {
                $invoiceId = (int) $selectedInvoice->id;
            }
        }
    
        $invoice = \App\Models\Invoice::with(['company', 'buyer', 'contract'])->find($invoiceId);
        if (!$invoice) {
            return 'Ne mogu da pronađem fakturu za izmjenu preview-a slanja.';
        }
    
        $this->rememberChatContext($invoice->contract, $invoice);
        $filename = 'faktura-' . preg_replace('/[^A-Za-z0-9\-]/', '-', $invoice->invoice_number) . '.pdf';
        $contractText = $invoice->contract ? $invoice->contract->contract_number : '-';
    
        session()->put('pending_chat_action', [
            'type' => 'send_invoice_email',
            'invoice_id' => $invoice->id,
            'email' => $email,
            'message' => trim(($pendingAction['message'] ?? '') . "\n" . $message),
        ]);
    
        return [
            'response' => "Izmijenio sam preview slanja fakture.\n\nPregled slanja fakture prije slanja:\nFaktura: {$invoice->invoice_number}\nUgovor: {$contractText}\nFirma: {$invoice->company->name}\nKupac: {$invoice->buyer->name}\nDatum: {$invoice->issued_at->format('Y-m-d')}\nUkupno za plaćanje: {$invoice->total_price_to_pay} EUR\nPrimaoc: {$email}\nPDF prilog: {$filename}",
            'quick_actions' => $this->confirmationQuickActions(),
        ];
    }
    
    private function messageMentionsInvoiceSelection(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        return $this->extractInvoiceNumber($message) !== null
            || $this->extractContractNumber($message) !== null
            || $this->extractInvoicePeriod($message) !== null
            || $this->extractInvoiceDate($message) !== null
            || str_contains($normalizedMessage, 'zadnja')
            || str_contains($normalizedMessage, 'poslednja')
            || str_contains($normalizedMessage, 'posljednja')
            || str_contains($normalizedMessage, 'najnovija')
            || str_contains($normalizedMessage, 'last')
            || str_contains($normalizedMessage, 'faktura')
            || str_contains($normalizedMessage, 'fakturu')
            || str_contains($normalizedMessage, 'invoice');
    }
    
    private function handlePendingActionResponse(Request $request, string $message, string $requestId): array|string
    {
        $normalizedMessage = trim(mb_strtolower($message));
        $pendingAction = session('pending_chat_action');
    
        if (!$pendingAction) {
            return 'Nema akcije koja čeka potvrdu.';
        }
    
        if (in_array($normalizedMessage, ['ne', 'no', 'otkazi', 'otkaži', 'cancel'], true)) {
            session()->forget('pending_chat_action');
    
            return 'U redu, nisam ništa upisao. Pošalji izmijenjen zahtjev kada budeš spreman.';
        }
    
        if (($pendingAction['type'] ?? null) === 'create_contract') {
            session()->forget('pending_chat_action');
    
            $response = $this->createContractFromPayload($pendingAction['payload'], $requestId, $pendingAction['message'] ?? '');
            $contractNumber = $pendingAction['payload']['contract_number'] ?? null;
            if ($contractNumber) {
                $contract = \App\Models\Contract::where('contract_number', $contractNumber)->first();
                if ($contract) {
                    $this->rememberChatContext($contract);
                }
            }
    
            return $response;
        }
    
        if (($pendingAction['type'] ?? null) === 'create_invoice') {
            session()->forget('pending_chat_action');
    
            $contract = \App\Models\Contract::with(['items.product.vatRate', 'company.users', 'buyer'])
                ->where('contract_number', $pendingAction['contract_number'])
                ->first();
    
            if (!$contract) {
                return "Ne mogu da pronađem ugovor {$pendingAction['contract_number']}.";
            }
    
            $issueDate = \Carbon\Carbon::parse($pendingAction['issue_date']);
            if ($this->invoiceExistsForContractPeriod($contract, $issueDate)) {
                return "Faktura za ugovor {$contract->contract_number} već postoji za period {$issueDate->format('m/Y')}.";
            }
    
            $invoice = $this->createInvoiceFromContract($contract, $issueDate, $requestId);
            $this->rememberChatContext($contract, $invoice);
            $items = $invoice->items->map(fn($item) => "- {$item->product->name}: {$item->quantity} x {$item->unit_price} EUR")->join("\n");
    
            return [
                'response' => "Kreirana je faktura {$invoice->invoice_number} za ugovor {$contract->contract_number}.\nDatum: {$invoice->issued_at->format('Y-m-d')}\nKupac: {$invoice->buyer->name}\nUkupno bez PDV-a: {$invoice->total_price_without_vat} EUR\nPDV: {$invoice->total_vat_amount} EUR\nUkupno za plaćanje: {$invoice->total_price_to_pay} EUR\nStavke:\n{$items}",
                'download_url' => route('invoice.pdf', ['id' => $invoice->id]),
                'download_label' => 'Preuzmi PDF',
                'quick_actions' => [
                    [
                        'label' => 'Pošalji na mejl',
                        'prefill' => 'Pošalji ovu fakturu na mejl: ',
                        'style' => 'secondary',
                    ],
                ],
            ];
        }
    
        if (($pendingAction['type'] ?? null) === 'send_invoice_email') {
            session()->forget('pending_chat_action');
    
            return $this->sendInvoiceEmail(
                (int) $pendingAction['invoice_id'],
                $pendingAction['email'],
                $requestId
            );
        }
    
        session()->forget('pending_chat_action');
    
        return 'Nacrt akcije nije prepoznat. Pošalji zahtjev ponovo.';
    }
}
