<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        return view('chat.index');
    }

    public function stream(Request $request)
{
    set_time_limit(120);

    $message = $request->input('message', '');
    $history = $request->input('history', []);
    $provider = $request->input('provider', 'ollama'); // 'ollama' ili 'apple'
    $requestId = bin2hex(random_bytes(8));

    if ($this->isPendingActionResponse($message)) {
        $startTime = microtime(true);
        $content = $this->handlePendingActionResponse($request, $message, $requestId);
        $elapsed = round(microtime(true) - $startTime, 2);

        \Log::info('Chat stats', [
            'provider'      => $provider,
            'request_id'    => $requestId,
            'message'       => $message,
            'action'        => 'pending_action_response',
            'php_elapsed_s' => $elapsed,
        ]);

        return response()->json([
            'response' => $content,
            'stats'    => ['time_s' => $elapsed, 'provider' => $provider, 'request_id' => $requestId, 'action' => 'pending_action_response']
        ]);
    }

    return $this->handleAiRoutedMessage($request, $message, $history, $provider, $requestId);
}

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
    $intent = $this->classifyChatIntent($message, $provider, $requestId);

    if (isset($intent['error'])) {
        return $this->chatJsonResponse($intent['error'], $provider, $requestId, 'intent_error', $startTime);
    }

    $intentName = $intent['intent'] ?? 'unknown';
    $confidence = (float) ($intent['confidence'] ?? 0);

    \Log::info('AI intent selected', [
        'request_id' => $requestId,
        'provider' => $provider,
        'message' => $message,
        'intent' => $intentName,
        'confidence' => $confidence,
        'raw_intent' => $intent,
    ]);

    if ($confidence < 0.45 || $intentName === 'unknown') {
        $content = "Mogu pomoći oko ovih akcija: kreiranje ugovora, kreiranje fakture, pregled ugovora, pregled faktura/stavki, lista firmi, statusi ugovora i nefiskalizovane fakture. Napiši šta želiš u vezi toga.";

        return $this->chatJsonResponse($content, $provider, $requestId, 'unknown_intent', $startTime);
    }

    $content = match ($intentName) {
        'small_talk' => 'Tu sam. Mogu pomoći oko ugovora, faktura, firmi i fiskalizacije.',
        'create_contract' => $this->handleCreateContractRequest($request, $message, $provider, $requestId),
        'create_invoice' => $this->handleCreateInvoiceRequest($request, $message, $provider, $requestId),
        'show_contract' => $this->handleShowContractRequest($message),
        'show_contract_items' => $this->handleShowContractItemsRequest($message),
        'show_contract_invoices' => $this->handleShowContractInvoicesRequest($message),
        'show_last_invoice' => $this->handleShowContractInvoicesRequest($this->ensureLastInvoiceWording($message)),
        'show_invoice' => $this->handleShowInvoiceRequest($message),
        'show_invoice_items' => $this->handleShowInvoiceItemsRequest($message),
        'send_invoice_email' => $this->handleSendInvoiceEmailRequest($message, $requestId),
        'download_invoice_pdf' => $this->handleDownloadInvoicePdfRequest($message),
        'contract_status_summary' => $this->handleContractStatusSummaryRequest(),
        'company_list' => $this->handleCompanyListRequest(),
        'unfiscalized_invoices' => $this->handleUnfiscalizedInvoicesRequest($message),
        default => "Mogu pomoći oko ovih akcija: kreiranje ugovora, kreiranje fakture, pregled ugovora, pregled faktura/stavki, lista firmi, statusi ugovora i nefiskalizovane fakture.",
    };

    if (is_array($content)) {
        return $this->chatJsonResponse($content['response'], $provider, $requestId, $intentName, $startTime, $content);
    }

    return $this->chatJsonResponse($content, $provider, $requestId, $intentName, $startTime);
}

private function classifyChatIntent(string $message, string $provider, string $requestId): array
{
    $systemPrompt = "Ti si intent router za FiscalizationME aplikaciju.
Vrati samo validan JSON, bez markdowna i bez objašnjenja.
Ne izvršavaš akcije i ne odgovaraš korisniku.
Tvoj posao je samo da prepoznaš jednu od dozvoljenih namjera.

Dozvoljeni intent-i:
- small_talk: pozdrav ili provjera da li je asistent tu
- create_contract: korisnik želi dodati/napraviti/kreirati ugovor
- create_invoice: korisnik želi dodati/napraviti/kreirati/fakturisati fakturu za ugovor
- show_contract: korisnik želi pregled konkretnog ugovora
- show_contract_items: korisnik želi stavke konkretnog ugovora
- show_contract_invoices: korisnik želi fakture konkretnog ugovora
- show_last_invoice: korisnik želi zadnju/posljednju/najnoviju fakturu za ugovor
- show_invoice: korisnik želi pregled konkretne fakture
- show_invoice_items: korisnik želi stavke konkretne fakture
- send_invoice_email: korisnik želi poslati fakturu/PDF fakture na email/mejl adresu
- download_invoice_pdf: korisnik želi PDF/preuzimanje/download fakture, uključujući zadnju fakturu za ugovor
- contract_status_summary: korisnik pita koliko ima aktivnih/neaktivnih/isteklih ugovora ili status ugovora u zbiru
- company_list: korisnik traži listu firmi/kompanija
- unfiscalized_invoices: korisnik traži nefiskalizovane fakture, fakture koje čekaju fiskalizaciju, ili pita da li je neka faktura/zadnja faktura fiskalizovana
- unknown: sve van navedenog opsega

Vrati JSON oblika:
{\"intent\":\"...\",\"confidence\":0.0,\"reason\":\"kratko\"}";

    $content = match ($provider) {
        'apple' => $this->callAppleIntentClassifier($message, $systemPrompt, $requestId),
        'gemini' => $this->callGemini($message, $systemPrompt, $requestId, 'gemini_intent_classifier'),
        default => $this->callOllama($message, [], $systemPrompt, $requestId, 'ollama_intent_classifier'),
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
        'small_talk',
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
        'contract_status_summary',
        'company_list',
        'unfiscalized_invoices',
        'unknown',
    ];

    if (!in_array($decoded['intent'] ?? null, $allowedIntents, true)) {
        return ['intent' => 'unknown', 'confidence' => 0, 'reason' => 'Intent nije dozvoljen.'];
    }

    return $decoded;
}

private function callAppleIntentClassifier(string $message, string $systemPrompt, string $requestId): string
{
    $prompt = "{$systemPrompt}\n\nUSER_MESSAGE:\n{$message}";

    $this->logPromptRequest($requestId, 'apple', 'apple_intent_classifier', [
        'prompt' => $prompt,
        'prompt_length' => strlen($prompt),
    ]);

    $ch = curl_init('http://localhost:8765');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $prompt);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlErrno !== 0) {
        $this->logPromptError($requestId, 'apple', 'apple_intent_classifier', [
            'http_code' => $httpCode,
            'curl_errno' => $curlErrno,
            'curl_error' => $curlError,
        ]);

        return 'Greška u komunikaciji sa Apple Intelligence servisom: ' . ($curlError ?: 'nepoznata greška.');
    }

    $this->logPromptResponse($requestId, 'apple', 'apple_intent_classifier', [
        'http_code' => $httpCode,
        'response' => $response,
        'response_length' => strlen($response),
    ]);

    return $response ?: 'Greška u komunikaciji sa Apple Intelligence servisom.';
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

    if ($contract && $this->isLastInvoiceQuestion($message)) {
        $invoice = \App\Models\Invoice::with(['company', 'buyer', 'contract'])
            ->where('contract_id', $contract->id)
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->first();

        if (!$invoice) {
            return "Ugovor {$contract->contract_number} nema fakture.";
        }

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

    return [
        'response' => "Spreman je PDF fakture {$invoice->invoice_number}{$contractText}.\nKlikni na dugme za preuzimanje.",
        'download_url' => $downloadUrl,
        'download_label' => 'Preuzmi PDF',
    ];
}

private function handleSendInvoiceEmailRequest(string $message, string $requestId): string
{
    $email = $this->extractEmailAddress($message);
    if (!$email) {
        return 'Mogu da pošaljem fakturu na mejl, samo mi treba validna email adresa. Npr. „pošalji zadnju fakturu za ugovor CTR-001 na mejl test@example.com”.';
    }

    $invoice = $this->findInvoiceForPdfRequest($message);
    if (!$invoice) {
        return 'Ne mogu da pronađem fakturu za slanje. Možeš napisati npr. „pošalji zadnju fakturu za ugovor CTR-001 na mejl test@example.com” ili „pošalji fakturu za april za CTR-001 na mejl test@example.com”.';
    }

    try {
        $invoice->loadMissing([
            'items.product.vatRate',
            'company',
            'buyer',
            'user',
            'contract',
        ]);

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
        return null;
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
        return null;
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
        return trim($matches[1], " \t\n\r\0\x0B.,;:");
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

    $period = $this->extractInvoicePeriod($message);
    $invoices = $contract->invoices->sortByDesc('issued_at');

    if ($period) {
        $invoices = $invoices->filter(fn($invoice) => $invoice->issued_at
            && (int) $invoice->issued_at->format('m') === $period['month']
            && (int) $invoice->issued_at->format('Y') === $period['year']);
    }

    if ($invoices->isEmpty()) {
        $periodText = $period ? " za period " . str_pad((string) $period['month'], 2, '0', STR_PAD_LEFT) . "/{$period['year']}" : '';

        return "Ugovor {$contract->contract_number} nema fakture{$periodText}.";
    }

    if ($this->isLastInvoiceQuestion($message)) {
        $invoice = $invoices->first();
        $status = $invoice->fic ? 'fiskalizovana' : 'nije fiskalizovana';

        return "Zadnja faktura za ugovor {$contract->contract_number}:\n- Broj fakture: {$invoice->invoice_number}\n- Datum: {$invoice->issued_at->format('Y-m-d')}\n- Ukupno bez PDV-a: {$invoice->total_price_without_vat} EUR\n- PDV: {$invoice->total_vat_amount} EUR\n- Ukupno za plaćanje: {$invoice->total_price_to_pay} EUR\n- Status: {$status}";
    }

    $lines = $invoices->map(function ($invoice) {
        $status = $invoice->fic ? 'fiskalizovana' : 'nije fiskalizovana';

        return "- {$invoice->invoice_number}: {$invoice->issued_at->format('Y-m-d')}, {$invoice->total_price_to_pay} EUR, {$status}";
    })->join("\n");

    $total = round($invoices->sum(fn($invoice) => (float) $invoice->total_price_to_pay), 2);
    $periodText = $period ? " za " . str_pad((string) $period['month'], 2, '0', STR_PAD_LEFT) . "/{$period['year']}" : '';

    return "Fakture ugovora {$contract->contract_number}{$periodText}:\n{$lines}\n\nBroj faktura: {$invoices->count()}\nUkupno fakturisano: {$total} EUR";
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

private function handleShowContractItemsRequest(string $message): string
{
    $contract = $this->findContractForChat($message);
    if (!$contract) {
        return 'Ne mogu da pronađem taj ugovor. Možeš napisati npr. „prikaži stavke za ctr 012” ili „stavke ugovora 12”.';
    }

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

    [$totalWithoutVat, $totalVat, $totalWithVat] = $this->calculateContractTotals($contract);
    $invoiceCount = $contract->invoices->count();
    $lastInvoice = $contract->invoices->sortByDesc('issued_at')->first();
    $lastInvoiceText = $lastInvoice
        ? "{$lastInvoice->invoice_number} ({$lastInvoice->issued_at->format('Y-m-d')}, {$lastInvoice->total_price_to_pay} EUR)"
        : 'nema faktura';

    return "Ugovor {$contract->contract_number}\nFirma: {$contract->company->name}\nKupac: {$contract->buyer->name}\nStatus: {$contract->status}\nPeriod: {$contract->start_date->format('Y-m-d')} - {$contract->end_date->format('Y-m-d')}\nKreiranje fakture: {$contract->billing_frequency}\nDan izdavanja: {$contract->issue_day}\nNačin plaćanja: {$contract->default_payment_method}\nBroj stavki: {$contract->items->count()}\nBroj faktura: {$invoiceCount}\nZadnja faktura: {$lastInvoiceText}\n\nUkupno bez PDV-a: {$totalWithoutVat} EUR\nPDV: {$totalVat} EUR\nUkupno za plaćanje: {$totalWithVat} EUR";
}

private function findContractForChat(string $message)
{
    $contractNumber = $this->extractContractNumber($message);
    if ($contractNumber === null) {
        return null;
    }

    return \App\Models\Contract::with([
        'company',
        'buyer',
        'items.product.vatRate',
        'items.vatRate',
        'invoices',
    ])->where('contract_number', $contractNumber)->first();
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

private function isPendingActionResponse(string $message): bool
{
    $normalizedMessage = trim(mb_strtolower($message));

    return session()->has('pending_chat_action') && (
        in_array($normalizedMessage, ['potvrdi', 'potvrdjujem', 'potvrđujem', 'da', 'yes', 'confirm'], true)
        || in_array($normalizedMessage, ['ne', 'no', 'otkazi', 'otkaži', 'cancel'], true)
    );
}

private function handlePendingActionResponse(Request $request, string $message, string $requestId): string
{
    $normalizedMessage = trim(mb_strtolower($message));
    $pendingAction = $request->session()->get('pending_chat_action');

    if (!$pendingAction) {
        return 'Nema akcije koja čeka potvrdu.';
    }

    if (in_array($normalizedMessage, ['ne', 'no', 'otkazi', 'otkaži', 'cancel'], true)) {
        $request->session()->forget('pending_chat_action');

        return 'U redu, nisam ništa upisao. Pošalji izmijenjen zahtjev kada budeš spreman.';
    }

    if (($pendingAction['type'] ?? null) === 'create_contract') {
        $request->session()->forget('pending_chat_action');

        return $this->createContractFromPayload($pendingAction['payload'], $requestId, $pendingAction['message'] ?? '');
    }

    if (($pendingAction['type'] ?? null) === 'create_invoice') {
        $request->session()->forget('pending_chat_action');

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
        $items = $invoice->items->map(fn($item) => "- {$item->product->name}: {$item->quantity} x {$item->unit_price} EUR")->join("\n");

        return "Kreirana je faktura {$invoice->invoice_number} za ugovor {$contract->contract_number}.\nDatum: {$invoice->issued_at->format('Y-m-d')}\nKupac: {$invoice->buyer->name}\nUkupno bez PDV-a: {$invoice->total_price_without_vat} EUR\nPDV: {$invoice->total_vat_amount} EUR\nUkupno za plaćanje: {$invoice->total_price_to_pay} EUR\nStavke:\n{$items}";
    }

    $request->session()->forget('pending_chat_action');

    return 'Nacrt akcije nije prepoznat. Pošalji zahtjev ponovo.';
}

private function handleCreateInvoiceRequest(Request $request, string $message, string $provider, string $requestId): string
{
    $contextJson = $this->buildInvoiceCreationContextJson($message);
    $extracted = $this->extractInvoicePayloadWithAi($message, $contextJson, $provider, $requestId);

    if (isset($extracted['error'])) {
        return $extracted['error'];
    }

    $contractNumber = $this->normalizeExtractedContractNumber($extracted['contract_number'] ?? null);
    if ($contractNumber === null) {
        return "Mogu da napravim fakturu, samo mi treba da prepoznam ugovor. Napiši prirodno, npr. „napravi fakturu za ugovor CTR-012” ili „fakturiši ugovor 12 za april 2026”.";
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

    $request->session()->put('pending_chat_action', [
        'type' => 'create_invoice',
        'contract_number' => $contractNumber,
        'issue_date' => $issueDate->toDateString(),
        'message' => $message,
    ]);

    return $preview . "\n\nAko je sve u redu, napiši: potvrdi\nAko nije, napiši: otkaži, pa pošalji izmijenjen zahtjev.";
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
    $systemPrompt = "Ti izvlačiš podatke za pripremu fakture u FiscalizationME aplikaciji.
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

private function handleCreateContractRequest(Request $request, string $message, string $provider, string $requestId): string
{
    $contextJson = $this->buildContractCreationContextJson($message);
    $extracted = $this->extractContractPayloadWithAi($message, $contextJson, $provider, $requestId);

    if (isset($extracted['error'])) {
        return $extracted['error'];
    }

    $validationErrors = $this->validateContractPayload($extracted, $message);
    if ($validationErrors !== []) {
        return "Ne mogu još da pripremim ugovor. Nedostaje ili nije validno:\n- " . implode("\n- ", $validationErrors) . "\n\nMožeš napisati prirodno, npr. „napravi ugovor između HardNet DOO i Crnogorski Telekom Servis od 29.04.2026 do 29.04.2027, sa 1 Internet paket i 2 Magenta paket” ili „... sa 1 Hosting paket po 15 EUR”.";
    }

    if (empty($extracted['contract_number'])) {
        $extracted['contract_number'] = $this->generateContractNumber();
    }

    $request->session()->put('pending_chat_action', [
        'type' => 'create_contract',
        'payload' => $extracted,
        'message' => $message,
    ]);

    return $this->buildContractPreviewFromPayload($extracted, $message) . "\n\nAko je sve u redu, napiši: potvrdi\nAko nije, napiši: otkaži, pa pošalji izmijenjen zahtjev.";
}

private function buildContractCreationContextJson(string $message): string
{
    $companies = \App\Models\Company::query()
        ->orderBy('name')
        ->get(['id', 'name', 'tax_id_number'])
        ->filter(fn($company) => $this->entityMatchesMessage($company->name, $message))
        ->whenEmpty(fn($collection) => \App\Models\Company::query()->orderBy('name')->take(30)->get(['id', 'name', 'tax_id_number']))
        ->map(fn($company) => [
            'id' => $company->id,
            'name' => $company->name,
            'tax_id_number' => $company->tax_id_number,
        ])
        ->values()
        ->all();

    $buyers = \App\Models\Buyer::query()
        ->orderBy('name')
        ->get(['id', 'name', 'tax_id_number'])
        ->filter(fn($buyer) => $this->entityMatchesMessage($buyer->name, $message))
        ->whenEmpty(fn($collection) => \App\Models\Buyer::query()->orderBy('name')->take(30)->get(['id', 'name', 'tax_id_number']))
        ->map(fn($buyer) => [
            'id' => $buyer->id,
            'name' => $buyer->name,
            'tax_id_number' => $buyer->tax_id_number,
        ])
        ->values()
        ->all();

    $products = \App\Models\Product::with('vatRate')
        ->orderBy('name')
        ->get()
        ->filter(fn($product) => $this->entityMatchesMessage($product->name, $message))
        ->whenEmpty(fn($collection) => \App\Models\Product::with('vatRate')->orderBy('name')->take(30)->get())
        ->map(fn($product) => [
            'id' => $product->id,
            'name' => $product->name,
            'code' => $product->code,
            'price' => $this->normalizeContractUnitPrice((float) $product->price),
            'unit' => $product->unit,
            'vat_rate_id' => $product->vat_rate_id,
            'vat_percentage' => $product->vatRate->percentage ?? null,
        ])
        ->values()
        ->all();

    $vatRates = \App\Models\VatRate::query()
        ->orderBy('percentage')
        ->get(['id', 'name', 'percentage'])
        ->map(fn($vatRate) => [
            'id' => $vatRate->id,
            'name' => $vatRate->name,
            'percentage' => $vatRate->percentage,
        ])
        ->values()
        ->all();

    return json_encode([
        'current_date' => now()->toDateString(),
        'companies' => $companies,
        'buyers' => $buyers,
        'products' => $products,
        'vat_rates' => $vatRates,
        'allowed_values' => [
            'billing_frequency' => ['monthly', 'quarterly', 'yearly'],
            'status' => ['active', 'paused', 'expired'],
            'default_type_of_invoice' => ['NONCASH', 'CASH'],
            'default_payment_method' => ['ACCOUNT', 'CARD', 'BANKNOTE', 'OTHER', 'VOUCHER', 'COMPENSATION'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

private function entityMatchesMessage(?string $name, string $message): bool
{
    if (!$name) {
        return false;
    }

    $normalizedName = mb_strtolower($name);
    $normalizedMessage = mb_strtolower($message);
    $compactName = preg_replace('/\s+/', '', $normalizedName);
    $compactMessage = preg_replace('/\s+/', '', $normalizedMessage);

    if (str_contains($normalizedMessage, $normalizedName) || str_contains($compactMessage, $compactName)) {
        return true;
    }

    $tokens = preg_split('/[^\p{L}\p{N}]+/u', $normalizedName, -1, PREG_SPLIT_NO_EMPTY);
    foreach ($tokens as $token) {
        if (mb_strlen($token) >= 4 && str_contains($normalizedMessage, $token)) {
            return true;
        }
    }

    return false;
}

private function extractContractPayloadWithAi(string $message, string $contextJson, string $provider, string $requestId): array
{
    $systemPrompt = "Ti izvlačiš podatke za pripremu ugovora u FiscalizationME aplikaciji.
Vrati samo validan JSON bez markdowna i bez objašnjenja.
Ne kreiraš ugovor i ne odgovaraš korisniku.
Koristi ID-jeve iz konteksta kada prepoznaš firmu, kupca ili proizvod.
Ako broj ugovora nije naveden, contract_number je null.
Ako issue_day nije naveden, koristi dan iz start_date, a ako ga nema koristi 1.
Ako billing_frequency nije naveden, koristi monthly.
Ako status nije naveden, koristi active.
Ako default_type_of_invoice nije naveden, koristi NONCASH.
Ako default_payment_method nije naveden, koristi ACCOUNT.
Ne izmišljaj stavke ugovora. Izvuci samo stavke koje su eksplicitno navedene u korisničkoj poruci.
Ako korisnička poruka ne pominje nijedan proizvod/uslugu/stavku, vrati items kao prazan niz.
Ako količina eksplicitno navedene stavke nije navedena, koristi 1.
Ako cijena eksplicitno navedene postojeće stavke nije navedena, koristi cijenu pronađenog proizvoda.
Ako korisnik navede novu stavku koja ne postoji u proizvodima, product_id je null, name je korisnikov naziv, price mora biti cijena iz poruke, a vat_rate_id koristi najbližu stopu iz konteksta ili 1.

KONTEKST:
{$contextJson}

Vrati JSON oblika:
{
  \"contract_number\": string|null,
  \"company_id\": number|null,
  \"buyer_id\": number|null,
  \"start_date\": \"YYYY-MM-DD\"|null,
  \"end_date\": \"YYYY-MM-DD\"|null,
  \"billing_frequency\": \"monthly\"|\"quarterly\"|\"yearly\",
  \"issue_day\": number,
  \"status\": \"active\"|\"paused\"|\"expired\",
  \"default_type_of_invoice\": \"NONCASH\"|\"CASH\",
  \"default_payment_method\": \"ACCOUNT\"|\"CARD\"|\"BANKNOTE\"|\"OTHER\"|\"VOUCHER\"|\"COMPENSATION\",
  \"items\": [{\"product_id\": number|null, \"name\": string, \"code\": string|null, \"quantity\": number, \"price\": number, \"vat_rate_id\": number|null}]
}";

    $content = match ($provider) {
        'apple' => $this->callAppleJsonExtractor($message, $systemPrompt, $requestId, 'apple_create_contract_extract'),
        'gemini' => $this->callGemini($message, $systemPrompt, $requestId, 'gemini_create_contract_extract'),
        default => $this->callOllama($message, [], $systemPrompt, $requestId, 'ollama_create_contract_extract'),
    };

    if (str_starts_with($content, 'Greška') || str_contains($content, 'unsupportedLanguageOrLocale')) {
        return ['error' => $content];
    }

    return $this->decodeJsonPayload($content, $requestId, $provider, $provider . '_create_contract_extract');
}

private function callAppleJsonExtractor(string $message, string $systemPrompt, string $requestId, string $promptType): string
{
    $prompt = "{$systemPrompt}\n\nUSER_MESSAGE:\n{$message}";

    $this->logPromptRequest($requestId, 'apple', $promptType, [
        'prompt' => $prompt,
        'prompt_length' => strlen($prompt),
    ]);

    $ch = curl_init('http://localhost:8765');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $prompt);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlErrno !== 0) {
        $this->logPromptError($requestId, 'apple', $promptType, [
            'http_code' => $httpCode,
            'curl_errno' => $curlErrno,
            'curl_error' => $curlError,
        ]);

        return 'Greška u komunikaciji sa Apple Intelligence servisom: ' . ($curlError ?: 'nepoznata greška.');
    }

    $this->logPromptResponse($requestId, 'apple', $promptType, [
        'http_code' => $httpCode,
        'response' => $response,
        'response_length' => strlen($response),
    ]);

    return $response ?: 'Greška u komunikaciji sa Apple Intelligence servisom.';
}

private function decodeJsonPayload(string $content, string $requestId, string $provider, string $promptType, ?string $errorMessage = null): array
{
    $json = trim($content);
    $json = preg_replace('/^```(?:json)?\s*/i', '', $json);
    $json = preg_replace('/\s*```$/', '', $json);

    $start = strpos($json, '{');
    $end = strrpos($json, '}');
    if ($start !== false && $end !== false && $end >= $start) {
        $json = substr($json, $start, $end - $start + 1);
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        $this->logPromptError($requestId, $provider, $promptType, [
            'json_error' => json_last_error_msg(),
            'raw_response' => $content,
        ]);

        return ['error' => $errorMessage ?: 'Model nije vratio validan JSON za kreiranje ugovora. Pokušaj precizniji prompt sa firmom, kupcem, datumima i stavkama.'];
    }

    return $data;
}

private function validateContractPayload(array $payload, string $message): array
{
    $errors = [];

    if (empty($payload['company_id']) || !\App\Models\Company::whereKey($payload['company_id'])->exists()) {
        $errors[] = 'firma/kompanija nije pronađena u sistemu';
    }

    if (empty($payload['buyer_id']) || !\App\Models\Buyer::whereKey($payload['buyer_id'])->exists()) {
        $errors[] = 'kupac nije pronađen u sistemu';
    }

    if (empty($payload['start_date']) || !strtotime($payload['start_date'])) {
        $errors[] = 'start_date nije validan datum';
    }

    if (empty($payload['end_date']) || !strtotime($payload['end_date'])) {
        $errors[] = 'end_date nije validan datum';
    }

    if (!empty($payload['start_date']) && !empty($payload['end_date']) && strtotime($payload['end_date']) <= strtotime($payload['start_date'])) {
        $errors[] = 'end_date mora biti poslije start_date';
    }

    if (empty($payload['items']) || !is_array($payload['items'])) {
        $errors[] = 'mora postojati bar jedna stavka ugovora';
    }

    if (!$this->messageMentionsContractItems($message, $payload['items'] ?? [])) {
        $errors[] = 'u poruci moraju biti eksplicitno navedene stavke ugovora; neću automatski birati proizvode iz kataloga';
    }

    if (!in_array($payload['billing_frequency'] ?? 'monthly', ['monthly', 'quarterly', 'yearly'], true)) {
        $errors[] = 'billing_frequency mora biti monthly, quarterly ili yearly';
    }

    if (!in_array($payload['status'] ?? 'active', ['active', 'paused', 'expired'], true)) {
        $errors[] = 'status mora biti active, paused ili expired';
    }

    if (!in_array($payload['default_type_of_invoice'] ?? 'NONCASH', ['NONCASH', 'CASH'], true)) {
        $errors[] = 'default_type_of_invoice mora biti NONCASH ili CASH';
    }

    if (!in_array($payload['default_payment_method'] ?? 'ACCOUNT', ['ACCOUNT', 'CARD', 'BANKNOTE', 'OTHER', 'VOUCHER', 'COMPENSATION'], true)) {
        $errors[] = 'default_payment_method nije validan';
    }

    foreach (($payload['items'] ?? []) as $index => $item) {
        if (empty($item['name']) && empty($item['product_id'])) {
            $errors[] = 'stavka ' . ($index + 1) . ' nema proizvod/naziv';
        }
        if (!isset($item['quantity']) || (float) $item['quantity'] <= 0) {
            $errors[] = 'stavka ' . ($index + 1) . ' nema validnu količinu';
        }
        if (!isset($item['price']) || (float) $item['price'] < 0) {
            $errors[] = 'stavka ' . ($index + 1) . ' nema validnu cijenu';
        }
        if (empty($item['product_id']) && (!isset($item['price']) || (float) $item['price'] <= 0)) {
            $errors[] = 'nova stavka ' . ($index + 1) . ' mora imati cijenu jer ne postoji u bazi proizvoda';
        }
    }

    return $errors;
}

private function messageMentionsContractItems(string $message, array $items = []): bool
{
    $normalizedMessage = mb_strtolower($message);

    if (
        str_contains($normalizedMessage, 'stavka')
        || str_contains($normalizedMessage, 'stavke')
        || str_contains($normalizedMessage, 'proizvod')
        || str_contains($normalizedMessage, 'proizvodi')
        || str_contains($normalizedMessage, 'usluga')
        || str_contains($normalizedMessage, 'usluge')
        || str_contains($normalizedMessage, 'item')
        || str_contains($normalizedMessage, 'items')
        || str_contains($normalizedMessage, 'product')
        || str_contains($normalizedMessage, 'service')
    ) {
        return true;
    }

    foreach ($items as $item) {
        $itemName = $item['name'] ?? null;
        if (is_string($itemName) && $this->entityMatchesMessage($itemName, $message)) {
            return true;
        }
    }

    if (preg_match('/\b\d+(?:[.,]\d+)?\s*x?\s*[\p{L}][\p{L}\p{N}\s\-_]{2,}?\s+(?:po|za|=)\s*\d+(?:[.,]\d+)?\s*(?:eur|€)\b/iu', $message) === 1) {
        return true;
    }

    $productNames = \App\Models\Product::query()->pluck('name');
    foreach ($productNames as $productName) {
        if ($productName && str_contains($normalizedMessage, mb_strtolower($productName))) {
            return true;
        }
    }

    return false;
}

private function createContractFromPayload(array $payload, string $requestId, string $message): string
{
    $contractNumber = $payload['contract_number'] ?: $this->generateContractNumber();
    if (\App\Models\Contract::where('contract_number', $contractNumber)->exists()) {
        return "Ugovor nije kreiran jer broj ugovora {$contractNumber} već postoji.";
    }

    $contract = \Illuminate\Support\Facades\DB::transaction(function () use ($payload, $contractNumber, $message) {
        $contract = \App\Models\Contract::create([
            'contract_number' => $contractNumber,
            'company_id' => $payload['company_id'],
            'buyer_id' => $payload['buyer_id'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
            'billing_frequency' => $payload['billing_frequency'] ?? 'monthly',
            'issue_day' => $payload['issue_day'] ?? 1,
            'status' => $payload['status'] ?? 'active',
            'default_type_of_invoice' => $payload['default_type_of_invoice'] ?? 'NONCASH',
            'default_payment_method' => $payload['default_payment_method'] ?? 'ACCOUNT',
        ]);

        foreach ($payload['items'] as $item) {
            $product = !empty($item['product_id'])
                ? \App\Models\Product::find($item['product_id'])
                : null;

            if (!$product) {
                $product = \App\Models\Product::updateOrCreate(
                    ['name' => $item['name']],
                    [
                        'code' => $item['code'] ?? '0000',
                        'price' => $this->normalizeContractUnitPrice((float) $item['price']),
                        'vat_rate_id' => $item['vat_rate_id'] ?? 1,
                        'unit' => 'kom',
                    ]
                );
            }

            $unitPrice = $this->resolveContractItemUnitPrice($item, $product, $message);

            $contract->items()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'vat_rate_id' => $product->vat_rate_id,
            ]);
        }

        return $contract->load('company', 'buyer', 'items.product');
    });

    \Log::info('LLM action completed', [
        'request_id' => $requestId,
        'action' => 'create_contract',
        'contract_id' => $contract->id,
        'contract_number' => $contract->contract_number,
    ]);

    $items = $contract->items->map(fn($item) => "- {$item->product->name}: {$item->quantity} x {$item->unit_price} EUR")->join("\n");

    return "Kreiran je ugovor {$contract->contract_number}.\nFirma: {$contract->company->name}\nKupac: {$contract->buyer->name}\nPeriod: {$contract->start_date->format('Y-m-d')} - {$contract->end_date->format('Y-m-d')}\nStavke:\n{$items}";
}

private function buildContractPreviewFromPayload(array $payload, string $message): string
{
    $company = \App\Models\Company::find($payload['company_id']);
    $buyer = \App\Models\Buyer::find($payload['buyer_id']);
    $contractNumber = $payload['contract_number'] ?: $this->generateContractNumber();
    $totalWithoutVat = 0;
    $totalVat = 0;
    $items = collect($payload['items'])->map(function ($item) use ($message) {
        $product = !empty($item['product_id']) ? \App\Models\Product::find($item['product_id']) : null;
        $name = $product->name ?? $item['name'];
        $price = $this->resolveContractItemUnitPrice($item, $product, $message);
        $quantity = $item['quantity'];
        $vatRate = $product->vatRate->percentage ?? \App\Models\VatRate::whereKey($item['vat_rate_id'] ?? null)->value('percentage');
        $vatText = $vatRate !== null ? ", PDV {$vatRate}%" : '';

        return "- {$name}: {$quantity} x {$price} EUR{$vatText}";
    })->join("\n");

    foreach ($payload['items'] as $item) {
        $product = !empty($item['product_id']) ? \App\Models\Product::find($item['product_id']) : null;
        $price = $this->resolveContractItemUnitPrice($item, $product, $message);
        $quantity = (float) $item['quantity'];
        $vatRate = (float) ($product->vatRate->percentage ?? \App\Models\VatRate::whereKey($item['vat_rate_id'] ?? null)->value('percentage') ?? 0);
        $base = round($quantity * $price, 2);
        $vat = round($base * ($vatRate / 100), 2);
        $totalWithoutVat += $base;
        $totalVat += $vat;
    }

    $totalWithoutVat = round($totalWithoutVat, 2);
    $totalVat = round($totalVat, 2);
    $totalWithVat = round($totalWithoutVat + $totalVat, 2);

    return "Pregled ugovora prije kreiranja:\nBroj ugovora: {$contractNumber}\nFirma: {$company->name}\nKupac: {$buyer->name}\nPeriod: {$payload['start_date']} - {$payload['end_date']}\nKreiranje fakture: " . ($payload['billing_frequency'] ?? 'monthly') . "\nDan izdavanja: " . ($payload['issue_day'] ?? 1) . "\nStatus: " . ($payload['status'] ?? 'active') . "\nNačin plaćanja: " . ($payload['default_payment_method'] ?? 'ACCOUNT') . "\nStavke:\n{$items}\n\nUkupno bez PDV-a: {$totalWithoutVat} EUR\nPDV: {$totalVat} EUR\nUkupno za plaćanje: {$totalWithVat} EUR";
}

private function resolveContractItemUnitPrice(array $item, $product, string $message): float
{
    $price = (float) ($item['price'] ?? 0);

    if ($product && !$this->messageMentionsExplicitPrice($message)) {
        $price = (float) $product->price;
    }

    return $this->normalizeContractUnitPrice($price);
}

private function normalizeContractUnitPrice(float $price): float
{
    // Neki postojeći podaci/model odgovori dolaze skalirani kao 60000 za 30.00 ili 50000 za 25.00.
    if ($price >= 10000) {
        return round($price / 2000, 4);
    }

    return round($price, 4);
}

private function messageMentionsExplicitPrice(string $message): bool
{
    $normalizedMessage = mb_strtolower($message);

    return str_contains($normalizedMessage, 'cijena')
        || str_contains($normalizedMessage, 'price')
        || preg_match('/\b\d+(?:[.,]\d+)?\s*(?:eur|€)\b/u', $normalizedMessage) === 1;
}

private function generateContractNumber(): string
{
    $lastId = (int) \App\Models\Contract::max('id');

    do {
        $lastId++;
        $contractNumber = 'CTR-' . str_pad((string) $lastId, 3, '0', STR_PAD_LEFT);
    } while (\App\Models\Contract::where('contract_number', $contractNumber)->exists());

    return $contractNumber;
}

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

private function callGemini(string $message, string $systemPrompt, ?string $requestId = null, string $promptType = 'gemini_main'): string
{
    $apiKey = config('services.gemini.key');

    if (!$apiKey) {
        $this->logPromptError($requestId, 'gemini', $promptType, [
            'error' => 'missing_api_key',
        ]);

        return 'Gemma API ključ nije podešen. Dodaj GEMINI_API_KEY u .env.';
    }

    $isStructuredPrompt = str_contains($promptType, 'classifier') || str_contains($promptType, 'extract');
    $maxOutputTokens = str_contains($promptType, 'create_contract_extract') ? 900 : ($isStructuredPrompt ? 300 : 1000);

    $payload = [
        'system_instruction' => [
            'parts' => [['text' => $systemPrompt]]
        ],
        'contents' => [
            ['role' => 'user', 'parts' => [['text' => $message]]]
        ],
        'generationConfig' => [
            'maxOutputTokens' => $maxOutputTokens,
            'temperature'     => $isStructuredPrompt ? 0.1 : 0.7,
        ]
    ];

    $this->logPromptRequest($requestId, 'gemini', $promptType, [
        'system_prompt' => $systemPrompt,
        'message' => $message,
        'prompt_length' => strlen($systemPrompt . "\n" . $message),
    ]);

    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/gemma-4-31b-it:generateContent?key={$apiKey}");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlErrno !== 0) {
        $this->logPromptError($requestId, 'gemini', $promptType, [
            'http_code' => $httpCode,
            'curl_errno' => $curlErrno,
            'curl_error' => $curlError,
        ]);

        return 'Greška pri komunikaciji sa Gemma servisom: ' . ($curlError ?: 'nepoznata greška.');
    }

    $this->logPromptResponse($requestId, 'gemini', $promptType, [
        'http_code' => $httpCode,
        'response' => $response,
        'response_length' => strlen($response),
    ]);

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $this->logPromptError($requestId, 'gemini', $promptType, [
            'http_code' => $httpCode,
            'json_error' => json_last_error_msg(),
            'raw_response' => $response,
        ]);

        return 'Greška pri obradi Gemma odgovora: ' . json_last_error_msg();
    }

    if (isset($data['error']['message'])) {
        $this->logPromptError($requestId, 'gemini', $promptType, [
            'http_code' => $httpCode,
            'api_error' => $data['error']['message'],
            'raw_response' => $response,
        ]);

        return 'Greška od Gemma API-ja: ' . $data['error']['message'];
    }

    // Izvuci samo ne-thought dijelove odgovora
    $parts = $data['candidates'][0]['content']['parts'] ?? [];
    $text = '';
    foreach ($parts as $part) {
        if (empty($part['thought'])) {
            $text .= $part['text'] ?? '';
        }
    }

    return trim($text) ?: 'Greška pri odgovoru od Gemma.';
}

private function callAppleIntelligence(string $message, string $promptDataJson, string $requestId): string
{
    // Apple Foundation Models trenutno ne podržava srpski kao jezik generisanja.
    // Zato Apple dobija prompt na engleskom, a odgovor se poslije prevodi na srpski.

    $prompt = "Answer only in English. Be short, concrete, and use only the provided FiscalizationME data.
Use only the JSON data. If a value is missing from the JSON, say that the value is not available.

DATA:
{$promptDataJson}

User question: {$message}";

    $this->logPromptRequest($requestId, 'apple', 'apple_main', [
        'prompt' => $prompt,
        'prompt_length' => strlen($prompt),
    ]);

    $ch = curl_init('http://localhost:8765');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $prompt);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlErrno !== 0) {
        $this->logPromptError($requestId, 'apple', 'apple_main', [
            'http_code' => $httpCode,
            'curl_errno' => $curlErrno,
            'curl_error' => $curlError,
        ]);

        return 'Greška u komunikaciji sa Apple Intelligence servisom: ' . ($curlError ?: 'nepoznata greška.');
    }

    $this->logPromptResponse($requestId, 'apple', 'apple_main', [
        'http_code' => $httpCode,
        'response' => $response,
        'response_length' => strlen($response),
    ]);

    return $response ?: 'Greška u komunikaciji sa Apple Intelligence servisom.';
}

private function isUnsupportedAppleLanguageError(string $content): bool
{
    return str_contains($content, 'unsupportedLanguageOrLocale')
        || str_contains($content, 'Unsupported language');
}

private function isAppleContextWindowError(string $content): bool
{
    return str_contains($content, 'exceededContextWindowSize')
        || str_contains($content, 'exceeds the maximum allowed context size');
}

private function translateToSerbian(string $content, string $requestId): string
{
    if ($content === '' || $this->isUnsupportedAppleLanguageError($content)) {
        return $content;
    }

    $prompt = "Prevedi sljedeći tekst na srpski jezik. Vrati samo prevod, bez objašnjenja:\n\n{$content}";

    return $this->callOllama($prompt, [], 'Ti si profesionalni prevodilac. Uvijek odgovaraj isključivo na srpskom jeziku.', $requestId, 'ollama_translate');
}

private function callOllama(string $message, array $history, string $systemPrompt, ?string $requestId = null, string $promptType = 'ollama'): string
{
    $messages = [];
    foreach ($history as $h) {
        $messages[] = ['role' => $h['role'], 'content' => $h['content']];
    }
    $messages[] = ['role' => 'user', 'content' => $message];
    $payload = [
        'model'    => 'qwen3.5:9b',
        'messages' => array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $messages
        ),
        'stream'  => false,
        'think'   => false,
        'options' => ['num_predict' => 400],
    ];

    $this->logPromptRequest($requestId, 'ollama', $promptType, [
        'system_prompt' => $systemPrompt,
        'messages' => $messages,
        'prompt_length' => strlen($systemPrompt . "\n" . $message),
        'model' => $payload['model'],
    ]);

    $ch = curl_init('http://localhost:11434/api/chat');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlErrno !== 0) {
        $this->logPromptError($requestId, 'ollama', $promptType, [
            'http_code' => $httpCode,
            'curl_errno' => $curlErrno,
            'curl_error' => $curlError,
        ]);

        return 'Greška pri komunikaciji sa Ollama servisom: ' . ($curlError ?: 'nepoznata greška.');
    }

    $this->logPromptResponse($requestId, 'ollama', $promptType, [
        'http_code' => $httpCode,
        'response' => $response,
        'response_length' => strlen($response),
    ]);

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $this->logPromptError($requestId, 'ollama', $promptType, [
            'http_code' => $httpCode,
            'json_error' => json_last_error_msg(),
            'raw_response' => $response,
        ]);

        return 'Greška pri obradi Ollama odgovora: ' . json_last_error_msg();
    }

    if (!isset($data['message']['content'])) {
        $this->logPromptError($requestId, 'ollama', $promptType, [
            'http_code' => $httpCode,
            'raw_response' => $response,
        ]);

        return 'Greška pri odgovoru.';
    }

    return $data['message']['content'];
}

private function logPromptRequest(?string $requestId, string $provider, string $promptType, array $context): void
{
    \Log::info('LLM prompt request', array_merge([
        'request_id' => $requestId,
        'provider' => $provider,
        'prompt_type' => $promptType,
    ], $context));
}

private function logPromptResponse(?string $requestId, string $provider, string $promptType, array $context): void
{
    \Log::info('LLM prompt response', array_merge([
        'request_id' => $requestId,
        'provider' => $provider,
        'prompt_type' => $promptType,
    ], $context));
}

private function logPromptError(?string $requestId, string $provider, string $promptType, array $context): void
{
    \Log::error('LLM prompt error', array_merge([
        'request_id' => $requestId,
        'provider' => $provider,
        'prompt_type' => $promptType,
    ], $context));
}
}
