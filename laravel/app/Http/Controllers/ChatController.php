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
    set_time_limit(60);

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

    if ($this->isCreateInvoiceRequest($message)) {
        $startTime = microtime(true);
        $content = $this->handleCreateInvoiceRequest($request, $message, $requestId);
        $elapsed = round(microtime(true) - $startTime, 2);

        \Log::info('Chat stats', [
            'provider'      => $provider,
            'request_id'    => $requestId,
            'message'       => $message,
            'action'        => 'create_invoice',
            'php_elapsed_s' => $elapsed,
        ]);

        return response()->json([
            'response' => $content,
            'stats'    => ['time_s' => $elapsed, 'provider' => $provider, 'request_id' => $requestId, 'action' => 'create_invoice']
        ]);
    }

    if ($this->isCreateContractRequest($message)) {
        $startTime = microtime(true);
        $content = $this->handleCreateContractRequest($request, $message, $provider, $requestId);
        $elapsed = round(microtime(true) - $startTime, 2);

        \Log::info('Chat stats', [
            'provider'      => $provider,
            'request_id'    => $requestId,
            'message'       => $message,
            'action'        => 'create_contract',
            'php_elapsed_s' => $elapsed,
        ]);

        return response()->json([
            'response' => $content,
            'stats'    => ['time_s' => $elapsed, 'provider' => $provider, 'request_id' => $requestId, 'action' => 'create_contract']
        ]);
    }

    $promptDataJson = $this->buildPromptDataJson($message, $provider);

    $systemPrompt = "Ti si asistent za FiscalizationME billing sistem u Crnoj Gori.
Odgovaraj kratko i konkretno na srpskom jeziku.
Koristi isključivo podatke iz JSON-a. Ako podatak ne postoji u JSON-u, reci da nemaš taj podatak.

PODACI_JSON:
{$promptDataJson}";

    $startTime = microtime(true);

    if ($provider === 'apple') {
        $content = $this->callAppleIntelligence($message, $promptDataJson, $requestId);

        if ($this->isUnsupportedAppleLanguageError($content)) {
            \Log::warning('LLM Apple unsupported language', [
                'request_id' => $requestId,
                'reason' => 'unsupported_language_or_locale',
                'apple_response' => $content,
            ]);

            $content = 'Apple Foundation Models trenutno ne podržava srpski/odabrani jezik za ovu sesiju. Za test Apple Intelligence koristi pitanje na engleskom ili promijeni Apple servis da uvijek koristi podržani locale, npr. en_US.';
        } elseif ($this->isAppleContextWindowError($content)) {
            \Log::warning('LLM Apple context window exceeded', [
                'request_id' => $requestId,
                'apple_response' => $content,
            ]);

            $content = 'Apple Foundation Models ima ograničenje konteksta od oko 4096 tokena. Pokušaj uži upit, npr. za jednu firmu, jedan ugovor ili jednu fakturu.';
        }
    } else {
        $content = $this->callOllama($message, $history, $systemPrompt, $requestId, 'ollama');
    }

    $elapsed = round(microtime(true) - $startTime, 2);

    \Log::info('Chat stats', [
        'provider'      => $provider,
        'request_id'    => $requestId,
        'message'       => $message,
        'php_elapsed_s' => $elapsed,
    ]);

    return response()->json([
        'response' => $content,
        'stats'    => ['time_s' => $elapsed, 'provider' => $provider, 'request_id' => $requestId]
    ]);
}

private function buildPromptDataJson(string $message, string $provider): string
{
    if ($provider === 'apple') {
        return $this->buildCompactApplePromptDataJson($message);
    }

    return $this->buildFullPromptDataJson();
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

private function handleCreateInvoiceRequest(Request $request, string $message, string $requestId): string
{
    $contractNumber = $this->extractContractNumber($message);
    if ($contractNumber === null) {
        return "Mogu da napravim fakturu, samo mi treba broj ugovora. Možeš napisati prirodno, npr. „napravi fakturu za ctr 012” ili „fakturiši ugovor 12 za april 2026”.";
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

    $issueDate = $this->extractInvoiceIssueDate($message);

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
    $contextJson = $this->buildContractCreationContextJson();
    $extracted = $provider === 'apple'
        ? $this->extractContractPayloadWithApple($message, $contextJson, $requestId)
        : $this->extractContractPayloadWithOllama($message, $contextJson, $requestId);

    if (isset($extracted['error'])) {
        return $extracted['error'];
    }

    $validationErrors = $this->validateContractPayload($extracted, $message);
    if ($validationErrors !== []) {
        return "Ne mogu još da pripremim ugovor. Nedostaje ili nije validno:\n- " . implode("\n- ", $validationErrors) . "\n\nMožeš napisati prirodno, npr. „napravi ugovor između HardNet DOO i Crnogorski Telekom Servis od 29.04.2026 do 29.04.2027, sa 1 Internet paket i 2 Magenta paket”.";
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

private function buildContractCreationContextJson(): string
{
    $companies = \App\Models\Company::query()
        ->orderBy('name')
        ->get(['id', 'name', 'tax_id_number'])
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

    return json_encode([
        'current_date' => now()->toDateString(),
        'companies' => $companies,
        'buyers' => $buyers,
        'products' => $products,
        'allowed_values' => [
            'billing_frequency' => ['monthly', 'quarterly', 'yearly'],
            'status' => ['active', 'paused', 'expired'],
            'default_type_of_invoice' => ['NONCASH', 'CASH'],
            'default_payment_method' => ['ACCOUNT', 'CARD', 'BANKNOTE', 'OTHER', 'VOUCHER', 'COMPENSATION'],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

private function extractContractPayloadWithApple(string $message, string $contextJson, string $requestId): array
{
    $prompt = "Extract contract creation data from the user message.
Return only valid JSON. Do not use markdown. Do not explain.
Use IDs from the provided context when company, buyer, or product names match.
If a contract_number is not provided, set it to null.
If issue_day is missing, infer it from start_date day; if still unknown, use 1.
If billing_frequency is missing, use monthly.
If status is missing, use active.
If default_type_of_invoice is missing, use NONCASH.
If default_payment_method is missing, use ACCOUNT.
Do not invent contract items. Only extract items explicitly mentioned in USER_MESSAGE.
If USER_MESSAGE does not explicitly mention any product/service/item, return items as an empty array.
If item quantity is missing for an explicitly mentioned item, use 1.
If item unit_price is missing for an explicitly mentioned item, use the matched product price.
Required JSON schema:
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
}

CONTEXT:
{$contextJson}

USER_MESSAGE:
{$message}";

    $this->logPromptRequest($requestId, 'apple', 'apple_create_contract_extract', [
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
        $this->logPromptError($requestId, 'apple', 'apple_create_contract_extract', [
            'http_code' => $httpCode,
            'curl_errno' => $curlErrno,
            'curl_error' => $curlError,
        ]);

        return ['error' => 'Apple servis nije dostupan: ' . ($curlError ?: 'nepoznata greška.')];
    }

    $this->logPromptResponse($requestId, 'apple', 'apple_create_contract_extract', [
        'http_code' => $httpCode,
        'response' => $response,
        'response_length' => strlen($response),
    ]);

    if ($this->isUnsupportedAppleLanguageError($response) || $this->isAppleContextWindowError($response)) {
        return ['error' => $response];
    }

    return $this->decodeJsonPayload($response, $requestId, 'apple', 'apple_create_contract_extract');
}

private function extractContractPayloadWithOllama(string $message, string $contextJson, string $requestId): array
{
    $systemPrompt = "Ti izvlačiš podatke za kreiranje ugovora.
Vrati samo validan JSON bez markdowna i bez objašnjenja.
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
Ako cijena eksplicitno navedene stavke nije navedena, koristi cijenu pronađenog proizvoda.

KONTEKST:
{$contextJson}";

    $content = $this->callOllama($message, [], $systemPrompt, $requestId, 'ollama_create_contract_extract');

    if (str_starts_with($content, 'Greška')) {
        return ['error' => $content];
    }

    return $this->decodeJsonPayload($content, $requestId, 'ollama', 'ollama_create_contract_extract');
}

private function decodeJsonPayload(string $content, string $requestId, string $provider, string $promptType): array
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

        return ['error' => 'Model nije vratio validan JSON za kreiranje ugovora. Pokušaj precizniji prompt sa firmom, kupcem, datumima i stavkama.'];
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

    if (!$this->messageMentionsContractItems($message)) {
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
    }

    return $errors;
}

private function messageMentionsContractItems(string $message): bool
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
        $vatRate = $product->vatRate->percentage ?? null;
        $vatText = $vatRate !== null ? ", PDV {$vatRate}%" : '';

        return "- {$name}: {$quantity} x {$price} EUR{$vatText}";
    })->join("\n");

    foreach ($payload['items'] as $item) {
        $product = !empty($item['product_id']) ? \App\Models\Product::find($item['product_id']) : null;
        $price = $this->resolveContractItemUnitPrice($item, $product, $message);
        $quantity = (float) $item['quantity'];
        $vatRate = (float) ($product->vatRate->percentage ?? 0);
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
