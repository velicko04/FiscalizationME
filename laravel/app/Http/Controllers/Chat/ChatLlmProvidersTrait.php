<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;

trait ChatLlmProvidersTrait
{
    private function callAppleIntentClassifier(string $message, string $systemPrompt, string $requestId): string
    {
        $prompt = $this->appleSafeText($systemPrompt, false) . "\n\nUSER_MESSAGE:\n" . $this->appleSafeText($message);
    
        $this->logPromptRequest($requestId, 'apple', 'apple_intent_classifier', [
            'prompt' => $prompt,
            'prompt_length' => strlen($prompt),
        ]);
    
        $ch = curl_init('http://localhost:8765');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain; charset=utf-8']);
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
    
    private function callAppleJsonExtractor(string $message, string $systemPrompt, string $requestId, string $promptType): string
    {
        $prompt = $this->appleSafeText($systemPrompt, false) . "\n\nUSER_MESSAGE:\n" . $this->appleSafeText($message);
    
        $this->logPromptRequest($requestId, 'apple', $promptType, [
            'prompt' => $prompt,
            'prompt_length' => strlen($prompt),
        ]);
    
        $ch = curl_init('http://localhost:8765');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain; charset=utf-8']);
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
    
    private function callGemini(string $message, string $systemPrompt, ?string $requestId = null, string $promptType = 'gemini_main'): string
    {
        $isStructuredPrompt = str_contains($promptType, 'classifier')
            || str_contains($promptType, 'extract')
            || str_contains($promptType, 'edit');
        $maxAttempts = $isStructuredPrompt ? 3 : 1;
    
        $payload = [
            'model' => 'gemma4:e4b', 
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
            'stream' => false
        ];
    
        $this->logPromptRequest($requestId, 'ollama', $promptType, [
            'system_prompt' => $systemPrompt,
            'message' => $message,
            'prompt_length' => strlen($systemPrompt . "\n" . $message),
        ]);
    
        $response = false;
        $curlError = '';
        $httpCode = 0;
    
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    
            $ch = curl_init("http://localhost:11434/api/chat");
    
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
            curl_close($ch);
    
            $shouldRetry = $response === false || in_array($httpCode, [429, 500, 502, 503, 504], true);
    
            if (!$shouldRetry || $attempt === $maxAttempts) {
                break;
            }
    
            \Log::warning('Ollama retry', [
                'request_id' => $requestId,
                'attempt' => $attempt,
                'http_code' => $httpCode,
                'curl_error' => $curlError,
            ]);
    
            usleep(200000 * $attempt);
        }
    
        if ($response === false) {
            $this->logPromptError($requestId, 'ollama', $promptType, [
                'curl_error' => $curlError,
            ]);
    
            return 'Greška pri komunikaciji sa lokalnim Gemma (Ollama) modelom: ' . $curlError;
        }
    
        $this->logPromptResponse($requestId, 'ollama', $promptType, [
            'response' => $response,
            'response_length' => strlen($response),
        ]);
    
        $data = json_decode($response, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logPromptError($requestId, 'ollama', $promptType, [
                'json_error' => json_last_error_msg(),
                'raw_response' => $response,
            ]);
    
            return 'Greška pri obradi Ollama odgovora: ' . json_last_error_msg();
        }
    
        // Ollama response format:
        $text = $data['message']['content'] ?? '';
    
        return trim($text) ?: 'Greška: prazan odgovor od Gemma (Ollama).';
    }
    
    private function callAppleIntelligence(string $message, string $promptDataJson, string $requestId): string
    {
        // Apple Foundation Models trenutno ne podržava srpski kao jezik generisanja.
        // Zato Apple dobija prompt na engleskom, a odgovor se poslije prevodi na srpski.
    
        $prompt = $this->appleSafeText("Answer only in English. Be short, concrete, and use only the provided FiscalizationME data.
    Use only the JSON data. If a value is missing from the JSON, say that the value is not available.
    The user may use local business words: ugovor=contract, faktura=invoice, firma=company, kupac=buyer.
    
    DATA:
    {$promptDataJson}
    
    User question: ", false) . $this->appleSafeText($message);
    
        $this->logPromptRequest($requestId, 'apple', 'apple_main', [
            'prompt' => $prompt,
            'prompt_length' => strlen($prompt),
        ]);
    
        $ch = curl_init('http://localhost:8765');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain; charset=utf-8']);
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
    
    private function appleSafeText(string $text, bool $normalizeBusinessTerms = true): string
    {
        $text = strtr($text, [
            'č' => 'c',
            'ć' => 'c',
            'š' => 's',
            'đ' => 'dj',
            'ž' => 'z',
            'Č' => 'C',
            'Ć' => 'C',
            'Š' => 'S',
            'Đ' => 'Dj',
            'Ž' => 'Z',
        ]);
    
        if (!$normalizeBusinessTerms) {
            return $text;
        }
    
        $replacements = [
            '/\bfakturisi\b/i' => 'create invoice',
            '/\bfakturise\b/i' => 'create invoice',
            '/\bfakturisanje\b/i' => 'invoice creation',
            '/\bnapravi\b/i' => 'create',
            '/\bkreiraj\b/i' => 'create',
            '/\bdodaj\b/i' => 'create',
            '/\bprikazi\b/i' => 'show',
            '/\bvidi\b/i' => 'show',
            '/\bdaj\b/i' => 'show',
            '/\bnadji\b/i' => 'find',
            '/\bugovor(a|u|om)?\b/i' => 'contract',
            '/\bfaktur(a|e|u|om)?\b/i' => 'invoice',
            '/\bracun(a|e|u|om)?\b/i' => 'invoice',
            '/\bstavk(a|e|u|ama)?\b/i' => 'items',
            '/\bfirma\b/i' => 'company',
            '/\bfirme\b/i' => 'companies',
            '/\bkupac\b/i' => 'buyer',
            '/\bkupca\b/i' => 'buyer',
            '/\bizmedju\b/i' => 'between',
            '/\bod\b/i' => 'from',
            '/\bdo\b/i' => 'to',
            '/\bza\b/i' => 'for',
            '/\bsa\b/i' => 'with',
            '/\bkoji\b/i' => 'that',
            '/\btraje\b/i' => 'lasts',
            '/\bzadnja\b/i' => 'last',
            '/\bzadnju\b/i' => 'last',
            '/\bposljednja\b/i' => 'last',
            '/\bposlednja\b/i' => 'last',
            '/\bposalji\b/i' => 'send',
            '/\bmejl\b/i' => 'email',
            '/\bnefiskalizovan(a|e|u|ih)?\b/i' => 'unfiscalized',
            '/\bfiskalizovan(a|e|u)?\b/i' => 'fiscalized',
            '/\bmjesec\b/i' => 'month',
            '/\bjanuar\b/i' => 'January',
            '/\bfebruar\b/i' => 'February',
            '/\bmart\b/i' => 'March',
            '/\bmaj\b/i' => 'May',
            '/\bjun\b/i' => 'June',
            '/\bjul\b/i' => 'July',
            '/\bavgust\b/i' => 'August',
            '/\bseptembar\b/i' => 'September',
            '/\boktobar\b/i' => 'October',
            '/\bnovembar\b/i' => 'November',
            '/\bdecembar\b/i' => 'December',
        ];
    
        return preg_replace(array_keys($replacements), array_values($replacements), $text) ?? $text;
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
