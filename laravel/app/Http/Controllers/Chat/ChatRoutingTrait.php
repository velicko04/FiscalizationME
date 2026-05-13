<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;

trait ChatRoutingTrait
{
    private function buildPromptDataJson(string $message, string $provider): string
    {
        if ($provider === 'apple') {
            return $this->buildCompactApplePromptDataJson($message);
        }
    
        return $this->buildFullPromptDataJson();
    }
    
    private function buildSystemPrompt(string $promptDataJson): string
    {
        return "Ti si asistent za FiscalizationME billing sistem u Crnoj Gori.
    Odgovaraj kratko i konkretno na srpskom jeziku.
    Koristi isključivo podatke iz JSON-a. Ako podatak ne postoji u JSON-u, reci da nemaš taj podatak.
    
    PODACI_JSON:
    {$promptDataJson}";
    }
    
    private function handleAiRoutedMessage(Request $request, string $message, array $history, string $provider, string $requestId)
    {
        $startTime = microtime(true);
    
        if (!$this->hasActiveDraft() && $this->isObviousCreateContractMessage($message)) {
            $content = $this->handleCreateContractRequest($request, $message, $provider, $requestId);
    
            if (is_array($content)) {
                $this->rememberToolResult('create_contract', $content['response'] ?? '');
                return $this->chatJsonResponse($content['response'], $provider, $requestId, 'create_contract_direct', $startTime, $content);
            }
    
            $this->rememberToolResult('create_contract', $content);
            return $this->chatJsonResponse($content, $provider, $requestId, 'create_contract_direct', $startTime);
        }
    
        $intent = $this->classifyChatIntent($message, $history, $provider, $requestId);
    
        if (isset($intent['error'])) {
            return $this->chatJsonResponse($intent['error'], $provider, $requestId, 'intent_error', $startTime);
        }
    
        $actions = $this->normalizeAiActions($intent);
        $intentName = count($actions) > 1 ? 'multi_action' : ($actions[0]['intent'] ?? ($intent['intent'] ?? 'unknown'));
        $confidence = (float) ($intent['confidence'] ?? 0);
        $entities = is_array($intent['entities'] ?? null) ? $intent['entities'] : [];
        session(['current_ai_entities' => $entities]);
    
        \Log::info('AI intent selected', [
            'request_id' => $requestId,
            'provider' => $provider,
            'message' => $message,
            'intent' => $intentName,
            'confidence' => $confidence,
            'actions' => $actions,
            'raw_intent' => $intent,
        ]);
    
        if ($this->hasActiveDraft() && $this->isLikelyDraftContinuation($message, $intentName)) {
            $content = $this->handleActiveDraftContinuation($request, $message, $provider, $requestId);
    
            if (is_array($content)) {
                $this->rememberToolResult('draft_continuation', $content['response'] ?? '');
                return $this->chatJsonResponse($content['response'], $provider, $requestId, 'draft_continuation', $startTime, $content);
            }
    
            $this->rememberToolResult('draft_continuation', $content);
            return $this->chatJsonResponse($content, $provider, $requestId, 'draft_continuation', $startTime);
        }
    
        if (($confidence < 0.45 || $intentName === 'unknown') && $this->hasActiveDraft()) {
            $content = $this->handleActiveDraftContinuation($request, $message, $provider, $requestId);
    
            if (is_array($content)) {
                $this->rememberToolResult('draft_continuation', $content['response'] ?? '');
                return $this->chatJsonResponse($content['response'], $provider, $requestId, 'draft_continuation', $startTime, $content);
            }
    
            $this->rememberToolResult('draft_continuation', $content);
            return $this->chatJsonResponse($content, $provider, $requestId, 'draft_continuation', $startTime);
        }
    
        if ($confidence < 0.45 || $intentName === 'unknown') {
            $content = $this->unsupportedChatScopeMessage();
    
            return $this->chatJsonResponse($content, $provider, $requestId, 'unknown_intent', $startTime);
        }
    
        if (count($actions) > 1) {
            $content = $this->handleMultipleAiActions($request, $message, $actions, $provider, $requestId);
        } else {
            $action = $actions[0] ?? ['intent' => $intentName, 'entities' => $entities];
            $content = $this->handleSingleAiAction($request, $message, $action, $provider, $requestId);
        }
    
        if (is_array($content)) {
            $this->rememberToolResult($intentName, $content['response'] ?? '');
            return $this->chatJsonResponse($content['response'], $provider, $requestId, $intentName, $startTime, $content);
        }
    
        $this->rememberToolResult($intentName, $content);
        return $this->chatJsonResponse($content, $provider, $requestId, $intentName, $startTime);
    }
    
    private function classifyChatIntent(string $message, array $history, string $provider, string $requestId): array
    {
        $conversationPayload = json_encode([
            'current_message' => $message,
            'recent_history' => collect($history)->take(-20)->values()->all(),
            'conversation_context' => session('chat_context', []),
            'active_draft' => session('chat_draft_action', null),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
        $systemPrompt = $provider === 'apple'
            ? "Return only valid JSON. No markdown.
    Task: choose the billing app intent for USER_MESSAGE.
    Allowed intents: create_contract, create_invoice, show_contract, show_contract_items, show_contract_invoices, show_last_invoice, show_invoice, show_invoice_items, send_invoice_email, download_invoice_pdf, unfiscalized_invoices, unknown.
    Return exactly:
    {\"intent\":\"...\",\"confidence\":0.0,\"entities\":{\"contract_number\":null,\"invoice_number\":null,\"company_name\":null,\"customer_name\":null,\"email\":null,\"date\":null,\"period\":null},\"reason\":\"...\"}
    Use actions only if USER_MESSAGE clearly asks for several actions joined by words like and/then/also:
    {\"intent\":\"multi_action\",\"confidence\":0.9,\"actions\":[{\"intent\":\"show_last_invoice\",\"entities\":{}},{\"intent\":\"download_invoice_pdf\",\"entities\":{}}],\"reason\":\"...\"}
    Mapping:
    create invoice/fakturisi contract => create_invoice.
    create contract => create_contract.
    show/find contract => show_contract.
    show contract items => show_contract_items.
    show invoices for contract => show_contract_invoices.
    last invoice => show_last_invoice.
    PDF/download => download_invoice_pdf.
    send/email/mail, only when email/send words are present => send_invoice_email.
    unfiscalized/fiscalized question => unfiscalized_invoices.
    Extract contract numbers like CTR-001, ctr 001, 001 into contract_number."
            : "Vrati samo validan JSON bez markdowna.
    Ti si conversational AI router za FiscalizationME. Koristi recent_history i conversation_context. Nikad ne ignoriši prethodni kontekst.
    Ako korisnik kaže 'taj ugovor', 'tog ugovora', 'njegove fakture', 'ta faktura', 'tu fakturu', 'ove stavke', koristi aktivni entitet iz conversation_context.
    Ako poruka nije o podržanim akcijama, vrati unknown.
    Podržani intenti:
    create_contract, create_invoice, show_contract, show_contract_items,
    show_contract_invoices, show_last_invoice, show_invoice, show_invoice_items,
    send_invoice_email, download_invoice_pdf, unfiscalized_invoices, unknown.
    Vrati JSON oblika:
    {\"intent\":\"...\",\"confidence\":0.0,\"entities\":{\"contract_number\":null,\"invoice_number\":null,\"company_name\":null,\"customer_name\":null,\"email\":null,\"date\":null,\"period\":null},\"reason\":\"...\"}
    Ako korisnik traži više stvari u jednoj poruci, dodaj i \"actions\" niz redom kojim treba izvršiti:
    {\"intent\":\"multi_action\",\"confidence\":0.0,\"actions\":[{\"intent\":\"show_last_invoice\",\"entities\":{}},{\"intent\":\"download_invoice_pdf\",\"entities\":{}},{\"intent\":\"send_invoice_email\",\"entities\":{\"email\":\"...\"}}],\"reason\":\"...\"}
    Pravila: slanje na mejl=>send_invoice_email; PDF/preuzimanje=>download_invoice_pdf;
    zadnja faktura za ugovor=>show_last_invoice; nefiskalizovane ili 'da li je fiskalizovana'=>unfiscalized_invoices;
    faktura/fakture za ugovor bez riječi napravi/kreiraj/fakturiši=>show_contract_invoices;
    zamjenice kao ta/tu/te fakture ili taj/tog ugovora odnose se na prethodni chat kontekst;
    napravi/dodaj ugovor=>create_contract; napravi/fakturiši ugovor=>create_invoice.";
    
        $content = match ($provider) {
            'apple' => $this->callAppleIntentClassifier($conversationPayload, $systemPrompt, $requestId),
            'gemini' => $this->callGemini($conversationPayload, $systemPrompt, $requestId, 'gemini_intent_classifier'),
            default => $this->callOllama($conversationPayload, [], $systemPrompt, $requestId, 'ollama_intent_classifier'),
        };
    
        if (
            str_starts_with($content, 'Greška')
            || str_contains($content, 'unsupportedLanguageOrLocale')
            || str_contains($content, 'API ključ')
        ) {
            return ['error' => $content];
        }
    
        $decoded = $this->decodeJsonPayload($content, $requestId, $provider, $provider . '_intent_classifier');
    
        if (isset($decoded['error'])) {
            return $decoded;
        }
    
        $allowedIntents = [
            'create_contract',
            'create_invoice',
            'show_contract',
            'show_contract_items',
            'show_contract_invoices',
            'show_last_invoice',
            'show_invoice',
            'show_invoice_items',
            'send_invoice_email',
            'download_invoice_pdf',
            'unfiscalized_invoices',
            'multi_action',
            'unknown',
        ];
    
        if (!in_array($decoded['intent'] ?? null, $allowedIntents, true)) {
            return ['intent' => 'unknown', 'confidence' => 0, 'reason' => 'Intent nije dozvoljen.'];
        }
    
        foreach (($decoded['actions'] ?? []) as $action) {
            if (!in_array($action['intent'] ?? null, array_diff($allowedIntents, ['multi_action']), true)) {
                return ['intent' => 'unknown', 'confidence' => 0, 'reason' => 'Jedna od akcija nije dozvoljena.'];
            }
        }
    
        return $decoded;
    }
    
    private function normalizeAiActions(array $intent): array
    {
        $actions = [];
    
        if (isset($intent['actions']) && is_array($intent['actions'])) {
            foreach ($intent['actions'] as $action) {
                if (!is_array($action) || empty($action['intent'])) {
                    continue;
                }
    
                $actions[] = [
                    'intent' => $action['intent'],
                    'entities' => is_array($action['entities'] ?? null) ? $action['entities'] : ($intent['entities'] ?? []),
                ];
            }
        }
    
        if ($actions === []) {
            $actions[] = [
                'intent' => $intent['intent'] ?? 'unknown',
                'entities' => is_array($intent['entities'] ?? null) ? $intent['entities'] : [],
            ];
        }
    
        return array_values(array_filter($actions, fn($action) => ($action['intent'] ?? 'unknown') !== 'multi_action'));
    }
    
    private function handleMultipleAiActions(Request $request, string $message, array $actions, string $provider, string $requestId): array
    {
        $responses = [];
        $extra = [];
    
        foreach ($actions as $index => $action) {
            $content = $this->handleSingleAiAction($request, $message, $action, $provider, $requestId);
            $intentName = $action['intent'] ?? 'unknown';
    
            if (is_array($content)) {
                $text = $content['response'] ?? '';
                $this->rememberToolResult($intentName, $text);
    
                foreach (['download_url', 'download_label', 'quick_actions'] as $key) {
                    if (isset($content[$key])) {
                        $extra[$key] = $content[$key];
                    }
                }
            } else {
                $text = $content;
                $this->rememberToolResult($intentName, $text);
            }
    
            $responses[] = ($index + 1) . ". " . trim($text);
    
            if (session()->has('pending_chat_action')) {
                if ($index < count($actions) - 1) {
                    $responses[] = "Zaustavio sam se na koraku koji traži potvrdu. Kada napišeš `potvrdi`, izvršiću tu akciju; zatim možeš tražiti naredni korak.";
                }
    
                break;
            }
        }
    
        return array_merge([
            'response' => implode("\n\n", $responses),
        ], $extra);
    }
    
    private function handleSingleAiAction(Request $request, string $message, array $action, string $provider, string $requestId)
    {
        $intentName = $action['intent'] ?? 'unknown';
        $entities = is_array($action['entities'] ?? null) ? $action['entities'] : [];
        session(['current_ai_entities' => $entities]);
        $resolvedMessage = $this->applyExtractedEntitiesToMessage($message, $entities, $intentName);
    
        return match ($intentName) {
            'create_contract' => $this->handleCreateContractRequest($request, $resolvedMessage, $provider, $requestId),
            'create_invoice' => $this->handleCreateInvoiceRequest($request, $resolvedMessage, $provider, $requestId),
            'show_contract' => $this->handleShowContractRequest($resolvedMessage),
            'show_contract_items' => $this->handleShowContractItemsRequest($resolvedMessage),
            'show_contract_invoices' => $this->handleShowContractInvoicesRequest($resolvedMessage),
            'show_last_invoice' => $this->handleShowContractInvoicesRequest($this->ensureLastInvoiceWording($resolvedMessage)),
            'show_invoice' => $this->extractContractNumber($resolvedMessage) !== null
                ? $this->handleShowContractInvoicesRequest($resolvedMessage)
                : $this->handleShowInvoiceRequest($resolvedMessage),
            'show_invoice_items' => $this->handleShowInvoiceItemsRequest($resolvedMessage),
            'send_invoice_email' => $this->handleSendInvoiceEmailRequest($request, $resolvedMessage),
            'download_invoice_pdf' => $this->handleDownloadInvoicePdfRequest($resolvedMessage),
            'unfiscalized_invoices' => $this->handleUnfiscalizedInvoicesRequest($resolvedMessage),
            default => $this->unsupportedChatScopeMessage(),
        };
    }
    
    private function applyExtractedEntitiesToMessage(string $message, array $entities, string $intentName): string
    {
        $parts = [$message];
    
        $contractNumber = $this->normalizeExtractedContractNumber($entities['contract_number'] ?? null);
        if ($contractNumber && $this->extractContractNumber($message) === null) {
            $parts[] = "ugovor {$contractNumber}";
        }
    
        $invoiceNumber = is_string($entities['invoice_number'] ?? null) ? trim($entities['invoice_number']) : null;
        if ($invoiceNumber && $this->extractInvoiceNumber($message) === null) {
            $parts[] = "faktura {$invoiceNumber}";
        }
    
        $email = is_string($entities['email'] ?? null) ? trim($entities['email']) : null;
        if ($email && !$this->extractEmailAddress($message)) {
            $parts[] = "mejl {$email}";
        }
    
        $date = is_string($entities['date'] ?? null) ? trim($entities['date']) : null;
        if ($date && !$this->extractInvoiceDate($message)) {
            $parts[] = $date;
        }
    
        $period = $entities['period'] ?? null;
        if (is_string($period) && trim($period) !== '' && !$this->extractInvoicePeriod($message)) {
            $parts[] = trim($period);
        }
    
        if (in_array($intentName, ['download_invoice_pdf', 'send_invoice_email', 'show_invoice', 'show_invoice_items'], true)
            && !$invoiceNumber
            && $this->extractInvoiceNumber($message) === null
            && $this->referencesPreviousInvoice($message)
        ) {
            $contextInvoiceNumber = session('chat_context.last_invoice_number');
            if ($contextInvoiceNumber) {
                $parts[] = "faktura {$contextInvoiceNumber}";
            }
        }
    
        if (in_array($intentName, ['show_contract', 'show_contract_items', 'show_contract_invoices', 'show_last_invoice', 'unfiscalized_invoices', 'create_invoice'], true)
            && !$contractNumber
            && $this->extractContractNumber($message) === null
            && $this->referencesPreviousContract($message)
        ) {
            $contextContractNumber = session('chat_context.last_contract_number');
            if ($contextContractNumber) {
                $parts[] = "ugovor {$contextContractNumber}";
            }
        }
    
        return implode(' ', array_filter($parts));
    }
    
    private function unsupportedChatScopeMessage(): string
    {
        return "Mogu pomoći samo oko ovih akcija:\n- prikaz ugovora i faktura\n- kreiranje ugovora i faktura\n- prikaz stavki ugovora\n- slanje fakture na mejl\n- preuzimanje PDF-a fakture\n- prikaz nefiskalizovanih faktura za ugovor\n\nNapiši zahtjev u vezi jedne od ovih akcija.";
    }
    
    private function hasActiveDraft(?string $type = null): bool
    {
        $draft = session('chat_draft_action');
    
        if (!is_array($draft) || empty($draft['type'])) {
            return false;
        }
    
        return $type === null || ($draft['type'] ?? null) === $type;
    }
    
    private function isDraftCancelResponse(string $message): bool
    {
        if (!$this->hasActiveDraft()) {
            return false;
        }
    
        $normalizedMessage = trim(mb_strtolower($message));
    
        return in_array($normalizedMessage, ['otkazi', 'otkaži', 'odustani', 'ponisti', 'poništi', 'cancel', 'reset'], true);
    }
    
    private function isLikelyDraftContinuation(string $message, string $intentName): bool
    {
        $draft = session('chat_draft_action', []);
        $draftType = $draft['type'] ?? null;
    
        if ($draftType === 'create_contract' && $intentName === 'create_contract') {
            return true;
        }
    
        if ($draftType === 'create_invoice' && $intentName === 'create_invoice') {
            return true;
        }
    
        $normalizedMessage = mb_strtolower(trim($message));
    
        if (preg_match('/^(vidi|prikaži|prikazi|show|pošalji|posalji|send|daj mi pdf|pdf|preuzmi|nefiskal|fiskaliz)/u', $normalizedMessage) === 1) {
            return false;
        }
    
    if (($draft['type'] ?? null) === 'create_invoice' && $this->extractContractNumber($message) !== null) {
        return true;
    }

    if (($draft['type'] ?? null) === 'create_contract' && $this->messageLooksLikeContractDraftEdit($message, $draft)) {
        return true;
    }

    return preg_match('/^(za|sa|od|do|kupac|firma|kompanija|period|stavka|stavke|proizvod|usluga|datum|traje|ima|dodaj|ukloni|obrisi|obriši|izbrisi|izbriši|promijeni|izmijeni|cijena|kosta|košta|i\s+)/u', $normalizedMessage) === 1
        || preg_match('/\b\d{1,2}[.\-\/]\d{1,2}[.\-\/]\d{4}\b/u', $normalizedMessage) === 1
        || preg_match('/\b\d+(?:[.,]\d+)?\s*(?:x|kom|eur|e|€)\b/u', $normalizedMessage) === 1;
}

private function isObviousCreateContractMessage(string $message): bool
    {
        $normalizedMessage = mb_strtolower($message);
    
        if (str_contains($normalizedMessage, 'faktura') || str_contains($normalizedMessage, 'fakturu') || str_contains($normalizedMessage, 'invoice')) {
            return false;
        }
    
        return (str_contains($normalizedMessage, 'ugovor') || str_contains($normalizedMessage, 'contract'))
            && (
                str_contains($normalizedMessage, 'napravi')
                || str_contains($normalizedMessage, 'kreiraj')
                || str_contains($normalizedMessage, 'dodaj')
                || str_contains($normalizedMessage, 'create')
                || str_contains($normalizedMessage, 'make')
            );
    }
    
    private function handleActiveDraftContinuation(Request $request, string $message, string $provider, string $requestId): array|string
    {
        $draft = session('chat_draft_action', []);
    
        return match ($draft['type'] ?? null) {
            'create_contract' => $this->handleCreateContractRequest($request, $message, $provider, $requestId),
            'create_invoice' => $this->handleCreateInvoiceRequest($request, $message, $provider, $requestId),
            default => $this->unsupportedChatScopeMessage(),
        };
    }
    
    private function ensureLastInvoiceWording(string $message): string
    {
        $normalizedMessage = mb_strtolower($message);
    
        if (
            str_contains($normalizedMessage, 'zadnja')
            || str_contains($normalizedMessage, 'poslednja')
            || str_contains($normalizedMessage, 'posljednja')
            || str_contains($normalizedMessage, 'najnovija')
            || str_contains($normalizedMessage, 'last')
        ) {
            return $message;
        }
    
        return $message . ' zadnja faktura';
    }
    
    private function chatJsonResponse(string $content, string $provider, string $requestId, string $action, float $startTime, array $extra = [])
    {
        $elapsed = round(microtime(true) - $startTime, 2);
    
        \Log::info('Chat stats', [
            'provider' => $provider,
            'request_id' => $requestId,
            'action' => $action,
            'php_elapsed_s' => $elapsed,
        ]);
    
        return response()->json(array_merge([
            'response' => $content,
            'stats' => ['time_s' => $elapsed, 'provider' => $provider, 'request_id' => $requestId, 'action' => $action],
        ], $extra));
    }
}
