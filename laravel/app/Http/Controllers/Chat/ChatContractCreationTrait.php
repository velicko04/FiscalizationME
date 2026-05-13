<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;

trait ChatContractCreationTrait
{
    private function handleCreateContractRequest(Request $request, string $message, string $provider, string $requestId): array|string
    {
        $normalizedMessage = $this->normalizeContractMessageForAi($message);
        $contextJson = $this->buildContractCreationContextJson($normalizedMessage);
        $existingDraft = $this->getDraftPayload('create_contract');
        $fastPayload = $existingDraft !== []
            ? $this->tryApplyContractDraftFastEdit($existingDraft, $normalizedMessage)
            : $this->tryBuildContractPayloadFast($normalizedMessage);
    
        if ($fastPayload !== null) {
            $extracted = $fastPayload;
            $usesDraftEdit = $existingDraft !== [];
        } else {
            $usesDraftEdit = $existingDraft !== [];
            $extracted = $usesDraftEdit
                ? $this->extractContractPreviewEditWithAi($existingDraft, $normalizedMessage, $contextJson, $provider, $requestId)
                : $this->extractContractPayloadWithAi($normalizedMessage, $contextJson, $provider, $requestId);
        }
    
        if (isset($extracted['error'])) {
            return $extracted['error'];
        }
    
        $extracted = $this->normalizeContractPayloadAfterAi($extracted, $normalizedMessage);
        $extracted = $usesDraftEdit
            ? $this->mergeContractDraftPayload([], $extracted)
            : $this->mergeContractDraftPayload($existingDraft, $extracted);
        $draftMessages = $this->draftSourceMessages('create_contract', $normalizedMessage);
        $combinedMessage = implode("\n", $draftMessages);
    
        $validationErrors = $this->validateContractPayload($extracted, $combinedMessage);
        if ($validationErrors !== []) {
            $this->storeDraftAction($request, 'create_contract', $extracted, $normalizedMessage);
    
            return $this->buildContractDraftQuestion($extracted, $validationErrors);
        }
    
        if (empty($extracted['contract_number'])) {
            $extracted['contract_number'] = $this->generateContractNumber();
        }
    
        session()->put('pending_chat_action', [
            'type' => 'create_contract',
            'payload' => $extracted,
            'message' => $combinedMessage,
        ]);
        session()->forget('chat_draft_action');
    
        return [
            'response' => $this->buildContractPreviewFromPayload($extracted, $combinedMessage),
            'quick_actions' => $this->confirmationQuickActions(),
        ];
    }
    
    private function buildContractCreationContextJson(string $message): string
    {
        $companies = \App\Models\Company::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn($company) => $this->entityMatchesMessage($company->name, $message))
            ->map(fn($company) => [
                'id' => $company->id,
                'name' => $company->name,
            ])
            ->values()
            ->all();
    
        $buyers = \App\Models\Buyer::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(fn($buyer) => $this->entityMatchesMessage($buyer->name, $message))
            ->map(fn($buyer) => [
                'id' => $buyer->id,
                'name' => $buyer->name,
            ])
            ->values()
            ->all();
    
        $products = \App\Models\Product::with('vatRate')
            ->orderBy('name')
            ->get()
            ->filter(fn($product) => $this->entityMatchesMessage($product->name, $message))
            ->map(fn($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $this->normalizeContractUnitPrice((float) $product->price),
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
            'defaults' => ['billing_frequency' => 'monthly', 'status' => 'active', 'invoice_type' => 'NONCASH', 'payment_method' => 'ACCOUNT'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    
    private function normalizeContractMessageForAi(string $message): string
    {
        $message = preg_replace('/\b(\d+(?:[.,]\d+)?)\s*e\b/iu', '$1 EUR', $message) ?? $message;
    
        return preg_replace('/\b(od|za)\s+(\d+(?:[.,]\d+)?)\s*(?:eur|€)\b/iu', 'po $2 EUR', $message) ?? $message;
    }
    
    private function normalizeContractPayloadAfterAi(array $payload, string $message): array
    {
        if (empty($payload['items']) || !is_array($payload['items'])) {
            return $payload;
        }
    
        $payload['items'] = array_map(function ($item) use ($message) {
            if (!is_array($item)) {
                return $item;
            }
    
            $name = trim((string) ($item['name'] ?? ''));
            $price = isset($item['price']) ? (float) $item['price'] : 0.0;
    
            if ($name !== '' && preg_match('/^(.*?)\s+(\d+(?:[.,]\d+)?)\s*(?:eur|€)$/iu', $name, $matches) === 1) {
                $name = trim($matches[1]);
                if ($price <= 0) {
                    $price = (float) str_replace(',', '.', $matches[2]);
                }
            }
    
            if ($price <= 0 && $name !== '') {
                $priceFromMessage = $this->extractPriceForItemName($message, $name);
                if ($priceFromMessage !== null) {
                    $price = $priceFromMessage;
                }
            }
    
            $item['name'] = $name;
            $item['price'] = $price;
    
            return $item;
        }, $payload['items']);
    
        return $payload;
    }
    
    private function extractPriceForItemName(string $message, string $itemName): ?float
    {
        $normalizedItem = $this->normalizeSearchText($itemName);
        if ($normalizedItem === '') {
            return null;
        }
    
        $segments = preg_split('/\s+(?:i|,|;)\s+/iu', $message) ?: [$message];
        foreach ($segments as $segment) {
            if (!str_contains($this->normalizeSearchText($segment), $normalizedItem)) {
                continue;
            }
    
            if (preg_match('/\b(?:po|za|od|=|je|kosta|košta)\s*(\d+(?:[.,]\d+)?)\s*(?:eur|€)\b/iu', $segment, $matches) === 1) {
                return (float) str_replace(',', '.', $matches[1]);
            }
        }
    
        return null;
    }
    
    private function tryBuildContractPayloadFast(string $message): ?array
    {
        if (!$this->isObviousCreateContractMessage($message)) {
            return null;
        }
    
        $company = $this->findMentionedCompany($message);
        $buyer = $this->findMentionedBuyer($message);
        [$startDate, $endDate] = $this->extractContractPeriodFast($message);
        $items = $this->extractContractItemsFast($message);
    
        if (!$company || !$buyer || !$startDate || !$endDate || $items === []) {
            return null;
        }
    
        return [
            'contract_number' => null,
            'company_id' => $company->id,
            'buyer_id' => $buyer->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'billing_frequency' => 'monthly',
            'issue_day' => (int) \Carbon\Carbon::parse($startDate)->format('d'),
            'status' => 'active',
            'default_type_of_invoice' => 'NONCASH',
            'default_payment_method' => 'ACCOUNT',
            'items' => $items,
        ];
    }
    
private function tryApplyContractDraftFastEdit(array $draft, string $message): ?array
{
        $updated = $draft;
        $changed = false;
        $normalizedMessage = mb_strtolower($message);
    
        [$startDate, $endDate] = $this->extractContractPeriodFast($message);
        if ($startDate && $endDate) {
            $updated['start_date'] = $startDate;
            $updated['end_date'] = $endDate;
            $updated['issue_day'] = (int) \Carbon\Carbon::parse($startDate)->format('d');
            $changed = true;
        }
    
        $newItems = $this->extractContractItemsFast($message);
        if ($newItems !== [] && preg_match('/\b(dodaj|ima|stavke|stavka|sa)\b/iu', $message) === 1) {
            $updated['items'] = $this->mergeContractDraftItems(is_array($updated['items'] ?? null) ? $updated['items'] : [], $newItems);
            $changed = true;
        }
    
        if (preg_match('/\b(ukloni|obrisi|obriši|izbrisi|izbriši|makni)\s+([\p{L}\p{N}\s\-_]+)/iu', $message, $matches) === 1) {
            $needle = $this->normalizeSearchText($matches[2]);
            $updated['items'] = array_values(array_filter($updated['items'] ?? [], function ($item) use ($needle) {
                $name = $this->normalizeSearchText((string) ($item['name'] ?? ''));
    
                return $needle === '' || (!str_contains($name, $needle) && !str_contains($needle, $name));
            }));
            $changed = true;
        }
    
        if (is_array($updated['items'] ?? null) && $updated['items'] !== []) {
            foreach ($updated['items'] as &$item) {
                $name = (string) ($item['name'] ?? '');
                $itemMentioned = $name !== '' && str_contains($this->normalizeSearchText($message), $this->normalizeSearchText($name));
    
                if (($itemMentioned || count($updated['items']) === 1)
                    && preg_match('/(?:nije|ne)\s+\d+(?:[.,]\d+)?\s+(?:nego|vec|već)\s+(\d+(?:[.,]\d+)?)/iu', $message, $matches) === 1
                ) {
                    if (str_contains($normalizedMessage, 'eur') || str_contains($normalizedMessage, '€') || str_contains($normalizedMessage, 'cijen') || str_contains($normalizedMessage, 'kosta') || str_contains($normalizedMessage, 'košta')) {
                        $item['price'] = (float) str_replace(',', '.', $matches[1]);
                    } else {
                        $item['quantity'] = (float) str_replace(',', '.', $matches[1]);
                    }
                    $changed = true;
                }
    
                if ($itemMentioned && preg_match('/\b(?:je|kosta|košta|bude|na)\s+(\d+(?:[.,]\d+)?)\s*(?:eur|€)\b/iu', $message, $matches) === 1) {
                    $item['price'] = (float) str_replace(',', '.', $matches[1]);
                    $changed = true;
                }
            }
            unset($item);
        }
    
    return $changed ? $updated : null;
}

private function messageLooksLikeContractDraftEdit(string $message, array $draft): bool
{
    if (preg_match('/\b\d+(?:[.,]\d+)?\s*(?:eur|e|€)\b/iu', $message) === 1) {
        return true;
    }

    if (preg_match('/\b(ukloni|obrisi|obriši|izbrisi|izbriši|makni|dodaj|promijeni|izmijeni|kosta|košta|cijena)\b/iu', $message) === 1) {
        return true;
    }

    $normalizedMessage = $this->normalizeSearchText($message);
    foreach (($draft['payload']['items'] ?? $draft['items'] ?? []) as $item) {
        $name = $this->normalizeSearchText((string) ($item['name'] ?? ''));
        if ($name !== '' && $this->messageHasFuzzyToken($normalizedMessage, $name)) {
            return true;
        }
    }

    return false;
}

private function findMentionedCompany(string $message)
{
        $normalizedMessage = $this->normalizeSearchText($message);
    
        return \App\Models\Company::query()
            ->get(['id', 'name'])
            ->filter(fn($company) => $this->entityMatchesMessage($company->name, $message))
            ->sortByDesc(fn($company) => $this->entityMentionScore($normalizedMessage, $company->name))
            ->first();
    }
    
    private function findMentionedBuyer(string $message)
    {
        $normalizedMessage = $this->normalizeSearchText($message);
    
        return \App\Models\Buyer::query()
            ->get(['id', 'name'])
            ->filter(fn($buyer) => $this->entityMatchesMessage($buyer->name, $message))
            ->sortByDesc(fn($buyer) => $this->entityMentionScore($normalizedMessage, $buyer->name))
            ->first();
    }
    
    private function entityMentionScore(string $normalizedMessage, string $name): int
    {
        $normalizedName = $this->normalizeSearchText($name);
    
        if ($normalizedName !== '' && str_contains($normalizedMessage, $normalizedName)) {
            return 1000 + strlen($normalizedName);
        }
    
        $score = 0;
        foreach (preg_split('/\s+/', mb_strtolower($name), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            $token = $this->normalizeSearchText($token);
            if (strlen($token) >= 4 && str_contains($normalizedMessage, $token)) {
                $score += strlen($token);
            }
        }
    
        return $score;
    }
    
    private function extractContractPeriodFast(string $message): array
    {
        if (preg_match('/\bod\s+(\d{1,2}[.\-\/]\d{1,2}[.\-\/]\d{4})\s+do\s+(\d{1,2}[.\-\/]\d{1,2}[.\-\/]\d{4})/iu', $message, $matches) === 1) {
            return [
                \Carbon\Carbon::parse(str_replace('.', '-', $matches[1]))->toDateString(),
                \Carbon\Carbon::parse(str_replace('.', '-', $matches[2]))->toDateString(),
            ];
        }
    
        $today = \Carbon\Carbon::today();
        if (preg_match('/\btraje\s+(mjesec|mesec)\s+dana\b/iu', $message) === 1) {
            return [$today->toDateString(), $today->copy()->addMonth()->toDateString()];
        }
    
        if (preg_match('/\btraje\s+(\d+)\s+(mjesec|mjeseca|mesec|meseca)\b/iu', $message, $matches) === 1) {
            return [$today->toDateString(), $today->copy()->addMonths((int) $matches[1])->toDateString()];
        }
    
        if (preg_match('/\btraje\s+godinu\s+dana\b/iu', $message) === 1) {
            return [$today->toDateString(), $today->copy()->addYear()->toDateString()];
        }
    
        return [null, null];
    }
    
    private function extractContractItemsFast(string $message): array
    {
        $itemsText = preg_split('/\b(?:ima|stavke su|stavka je|sa)\b/iu', $message, 2);
        $itemsText = trim($itemsText[1] ?? '');
        if ($itemsText === '') {
            return [];
        }
    
        $segments = preg_split('/\s+i\s+|[,;]+/iu', $itemsText) ?: [];
        $items = [];
    
        foreach ($segments as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }
    
            if (preg_match('/^(\d+(?:[.,]\d+)?)\s+(.+?)\s+(?:po|od|za)\s+(\d+(?:[.,]\d+)?)\s*(?:eur|€)?$/iu', $segment, $matches) !== 1
                && preg_match('/^(\d+(?:[.,]\d+)?)\s+(.+?)\s+(\d+(?:[.,]\d+)?)\s*(?:eur|€)$/iu', $segment, $matches) !== 1
            ) {
                continue;
            }
    
            $name = trim($matches[2]);
            $product = $this->findProductByName($name);
            $items[] = [
                'product_id' => $product?->id,
                'name' => $product?->name ?? $name,
                'code' => null,
                'quantity' => (float) str_replace(',', '.', $matches[1]),
                'price' => (float) str_replace(',', '.', $matches[3]),
                'vat_rate_id' => $product?->vat_rate_id ?? $this->defaultVatRateId(),
            ];
        }
    
        return $items;
    }
    
    private function findProductByName(string $name)
    {
        $normalizedNeedle = $this->normalizeSearchText($name);
    
        return \App\Models\Product::query()
            ->get(['id', 'name', 'vat_rate_id'])
            ->sortByDesc(fn($product) => $this->fuzzyNameScore($normalizedNeedle, $product->name))
            ->first(fn($product) => $this->fuzzyNameScore($normalizedNeedle, $product->name) >= 85);
    }
    
    private function mergeContractDraftPayload(array $draft, array $current): array
    {
        $payload = [
            'contract_number' => $this->firstFilledValue($current['contract_number'] ?? null, $draft['contract_number'] ?? null),
            'company_id' => $this->firstFilledValue($current['company_id'] ?? null, $draft['company_id'] ?? null),
            'buyer_id' => $this->firstFilledValue($current['buyer_id'] ?? null, $draft['buyer_id'] ?? null),
            'start_date' => $this->firstFilledValue($current['start_date'] ?? null, $draft['start_date'] ?? null),
            'end_date' => $this->firstFilledValue($current['end_date'] ?? null, $draft['end_date'] ?? null),
            'billing_frequency' => $this->firstFilledValue($draft['billing_frequency'] ?? null, $current['billing_frequency'] ?? null, 'monthly'),
            'issue_day' => !empty($current['start_date'])
                ? $this->firstFilledValue($current['issue_day'] ?? null, $draft['issue_day'] ?? null, 1)
                : $this->firstFilledValue($draft['issue_day'] ?? null, $current['issue_day'] ?? null, 1),
            'status' => $this->firstFilledValue($draft['status'] ?? null, $current['status'] ?? null, 'active'),
            'default_type_of_invoice' => $this->firstFilledValue($draft['default_type_of_invoice'] ?? null, $current['default_type_of_invoice'] ?? null, 'NONCASH'),
            'default_payment_method' => $this->firstFilledValue($draft['default_payment_method'] ?? null, $current['default_payment_method'] ?? null, 'ACCOUNT'),
            'items' => [],
        ];
    
        $draftItems = is_array($draft['items'] ?? null) ? $draft['items'] : [];
        $currentItems = is_array($current['items'] ?? null) ? $current['items'] : [];
        $payload['items'] = $this->mergeContractDraftItems($draftItems, $currentItems);
    
        return $payload;
    }
    
    private function mergeContractDraftItems(array $draftItems, array $currentItems): array
    {
        $items = [];
    
        foreach (array_merge($draftItems, $currentItems) as $item) {
            if (!is_array($item)) {
                continue;
            }
    
            $name = trim((string) ($item['name'] ?? ''));
            $productId = $item['product_id'] ?? null;
            if ($name === '' && empty($productId)) {
                continue;
            }
    
            $normalizedKey = $productId ? 'product:' . $productId : 'name:' . $this->normalizeSearchText($name);
            $items[$normalizedKey] = [
                'product_id' => $productId ? (int) $productId : null,
                'name' => $name,
                'code' => $item['code'] ?? null,
                'quantity' => isset($item['quantity']) ? (float) $item['quantity'] : 1,
                'price' => isset($item['price']) ? (float) $item['price'] : 0,
                'vat_rate_id' => $item['vat_rate_id'] ?? $this->defaultVatRateId(),
            ];
        }
    
        return array_values($items);
    }
    
    private function defaultVatRateId(): int
    {
        return (int) (\App\Models\VatRate::where('percentage', 21)->value('id')
            ?? \App\Models\VatRate::query()->orderByDesc('percentage')->value('id')
            ?? 1);
    }
    
    private function firstFilledValue(...$values)
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
    
            if (is_int($value) || is_float($value)) {
                return $value;
            }
    
            if ($value !== null && $value !== '' && $value !== []) {
                return $value;
            }
        }
    
        return null;
    }
    
    private function buildContractDraftQuestion(array $payload, array $validationErrors): string
    {
        $summary = $this->summarizeContractDraftPayload($payload);
        $summaryText = $summary ? "Imam:\n- " . implode("\n- ", $summary) . "\n\n" : '';
        $missing = $this->humanizeContractDraftErrors($validationErrors);
    
        return $summaryText
            . "Fali:\n- "
            . implode("\n- ", $missing)
            . "\n\nDopiši samo ono što fali, npr. „kupac je Telekom”, „traje mjesec dana” ili „punjač je 5e”.";
    }
    
    private function summarizeContractDraftPayload(array $payload): array
    {
        $summary = [];
    
        if (!empty($payload['company_id'])) {
            $summary[] = 'Firma: ' . (\App\Models\Company::whereKey($payload['company_id'])->value('name') ?: $payload['company_id']);
        }
    
        if (!empty($payload['buyer_id'])) {
            $summary[] = 'Kupac: ' . (\App\Models\Buyer::whereKey($payload['buyer_id'])->value('name') ?: $payload['buyer_id']);
        }
    
        if (!empty($payload['start_date']) || !empty($payload['end_date'])) {
            $summary[] = 'Period: ' . ($payload['start_date'] ?? '?') . ' - ' . ($payload['end_date'] ?? '?');
        }
    
        if (!empty($payload['items']) && is_array($payload['items'])) {
            $items = collect($payload['items'])->map(function ($item) {
                $productName = !empty($item['product_id'])
                    ? \App\Models\Product::whereKey($item['product_id'])->value('name')
                    : null;
                $name = $productName ?: ($item['name'] ?? 'stavka');
    
                return "{$name}: " . ($item['quantity'] ?? '?') . " x " . ($item['price'] ?? '?') . " EUR";
            })->join('; ');
    
            $summary[] = "Stavke: {$items}";
        }
    
        return $summary;
    }
    
    private function humanizeContractDraftErrors(array $validationErrors): array
    {
        $items = [];
    
        foreach ($validationErrors as $error) {
            $text = match (true) {
                str_contains($error, 'firma/kompanija') => 'firma',
                str_contains($error, 'kupac') => 'kupac',
                str_contains($error, 'start_date') || str_contains($error, 'end_date') => 'period ugovora',
                str_contains($error, 'bar jedna stavka') || str_contains($error, 'eksplicitno navedene stavke') => 'stavke ugovora',
                str_contains($error, 'mora imati cijenu') => 'cijena za novu stavku',
                default => $error,
            };
    
            $items[$text] = true;
        }
    
        return array_keys($items);
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
        $systemPrompt = $provider === 'apple'
            ? "Return only JSON for a contract draft. Use IDs from CONTEXT when names match.
    Do not invent items: extract only items mentioned in the user message. If a new item is not in products, product_id=null and price must come from the user message.
    Price can be written as 'po 4 EUR', 'od 4 EUR', 'za 4 EUR', '4e', or '4€'. Treat all as price 4. The price is never part of the item name.
    Defaults: billing_frequency=monthly, status=active, default_type_of_invoice=NONCASH, default_payment_method=ACCOUNT, issue_day=start_date day or 1.
    If company, buyer, or product is not in CONTEXT, the matching id must be null.
    The user's text can contain local terms such as ugovor=contract, izmedju=between, firma=company, kupac=buyer, stavke=items.
    CONTEXT: {$contextJson}
    JSON schema:
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
    }"
            : "Vrati samo JSON za nacrt ugovora. Koristi ID iz konteksta kad se naziv poklapa.
    Ne izmišljaj stavke: izvuci samo stavke pomenute u poruci. Ako nova stavka nije u products, product_id=null i cijena mora biti iz poruke.
    Cijena može biti napisana kao „po 4 EUR”, „od 4 EUR”, „za 4 EUR”, „4e” ili „4€”. Sve to znači cijena 4. Cijena nikad nije dio naziva stavke.
    Default: billing_frequency=monthly, status=active, default_type_of_invoice=NONCASH, default_payment_method=ACCOUNT, issue_day=dan start_date ili 1.
    Ako firma/kupac/proizvod nije u kontekstu, odgovarajući id je null.
    KONTEKST: {$contextJson}
    JSON schema:
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
    
    private function extractContractPreviewEditWithAi(array $currentPayload, string $message, string $contextJson, string $provider, string $requestId): array
    {
        $currentPayloadJson = json_encode($currentPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $systemPrompt = $provider === 'apple'
            ? "Return only JSON. Apply USER_MESSAGE as an edit to CURRENT_CONTRACT_DRAFT.
    Keep every unchanged field exactly as it is. If the user asks to add an item, append it. If the user asks to remove/delete an item, remove the best matching item by name. If the user changes price, quantity, period, company, buyer, or date, update only that field.
    When adding a new item not in CONTEXT products, product_id=null, quantity defaults to 1 if missing, and price must come from USER_MESSAGE.
    Understand natural corrections: 'not 3 but 5' changes quantity, 'not 80 but 60' changes price, 'chair should be 60' changes that item's price, 'add one more chair' increases quantity by 1.
    Never scale prices. 30e means 30.00, not 300 or 30000. 80e means 80.00.
    Return the complete updated contract draft in the same schema.
    
    CURRENT_CONTRACT_DRAFT:
    {$currentPayloadJson}
    
    CONTEXT:
    {$contextJson}"
            : "Vrati samo JSON. Primijeni USER_MESSAGE kao izmjenu na CURRENT_CONTRACT_DRAFT.
    Zadrži svako nepromijenjeno polje tačno kako jeste. Ako korisnik traži dodavanje stavke, dodaj je. Ako traži uklanjanje/brisanje stavke, ukloni najbolji pogodak po nazivu. Ako mijenja cijenu, količinu, period, firmu, kupca ili datum, izmijeni samo to polje.
    Ako dodaje novu stavku koja nije u CONTEXT products, product_id=null, quantity je 1 ako nije navedena, a price mora biti iz USER_MESSAGE.
    Razumij prirodne korekcije: „ne 3 nego 5” mijenja količinu, „nije 80 nego 60” mijenja cijenu, „stolica neka bude 60” mijenja cijenu te stavke, „dodaj još jednu stolicu” uvećava količinu za 1.
    Nikad ne skaliraj cijene. 30e znači 30.00, ne 300 ili 30000. 80e znači 80.00.
    Vrati kompletan ažurirani nacrt ugovora u istoj JSON šemi.
    
    CURRENT_CONTRACT_DRAFT:
    {$currentPayloadJson}
    
    CONTEXT:
    {$contextJson}";
    
        $content = match ($provider) {
            'apple' => $this->callAppleJsonExtractor($message, $systemPrompt, $requestId, 'apple_contract_preview_edit'),
            'gemini' => $this->callGemini($message, $systemPrompt, $requestId, 'gemini_contract_preview_edit'),
            default => $this->callOllama($message, [], $systemPrompt, $requestId, 'ollama_contract_preview_edit'),
        };
    
        if (str_starts_with($content, 'Greška') || str_contains($content, 'unsupportedLanguageOrLocale')) {
            return ['error' => $content];
        }
    
        return $this->decodeJsonPayload(
            $content,
            $requestId,
            $provider,
            $provider . '_contract_preview_edit',
            'Model nije vratio validan JSON za izmjenu preview-a. Pokušaj npr. „dodaj 1 stolicu po 80 EUR” ili „ukloni čarape”.'
        );
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
        $searchMessage = $this->normalizeSearchText($message);
    
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
            $normalizedItemName = is_string($itemName) ? $this->normalizeSearchText($itemName) : '';
            if ($normalizedItemName !== '' && (str_contains($searchMessage, $normalizedItemName) || str_contains($normalizedItemName, $searchMessage))) {
                return true;
            }
    
            if ($normalizedItemName !== '' && $this->messageHasFuzzyToken($searchMessage, $normalizedItemName)) {
                return true;
            }
        }
    
        if (preg_match('/\b\d+(?:[.,]\d+)?\s*x?\s*[\p{L}][\p{L}\p{N}\s\-_]{2,}?\s+(?:po|za|od|=)\s*\d+(?:[.,]\d+)?\s*(?:eur|e|€)\b/iu', $message) === 1) {
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
    
    private function messageHasFuzzyToken(string $normalizedMessage, string $normalizedNeedle): bool
    {
        foreach (preg_split('/[^a-z0-9]+/', $normalizedMessage, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $token) {
            if (strlen($token) < 4) {
                continue;
            }
    
            if (str_contains($token, $normalizedNeedle) || str_contains($normalizedNeedle, $token)) {
                return true;
            }
    
            if ($this->fuzzyNameScore($normalizedNeedle, $token) >= 80) {
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
    
        $this->rememberChatContext($contract);
    
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
            || preg_match('/\b\d+(?:[.,]\d+)?\s*(?:eur|e|€)\b/u', $normalizedMessage) === 1;
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
}
