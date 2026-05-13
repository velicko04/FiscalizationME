<?php

namespace App\Console\Commands;

use App\Http\Controllers\ChatController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use ReflectionMethod;

class ContractChatMatrix extends Command
{
    protected $signature = 'chat:contract-matrix {--provider=gemini}';

    protected $description = 'Run a lightweight chat contract creation matrix without writing contracts to the database.';

    public function handle(): int
    {
        $provider = (string) $this->option('provider');
        $results = [
            $this->scenarioCompleteContract($provider),
            $this->scenarioCurrencyVariants($provider),
            $this->scenarioDraftCompletion($provider),
            $this->scenarioPreviewEdits($provider),
        ];

        $failed = collect($results)->filter(fn($result) => !$result['passed']);

        $this->newLine();
        $this->table(['Scenario', 'Result', 'Time'], collect($results)->map(fn($result) => [
            $result['name'],
            $result['passed'] ? 'PASS' : 'FAIL',
            $result['time'] . 's',
        ])->all());

        if ($failed->isNotEmpty()) {
            $this->newLine();
            foreach ($failed as $result) {
                $this->error($result['name']);
                foreach ($result['errors'] as $error) {
                    $this->line(' - ' . $error);
                }
                $this->line($result['last_response']);
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function scenarioCompleteContract(string $provider): array
    {
        return $this->runScenario('complete natural prompt', [[
            'message' => 'Napravi ugovor firma HardNet kupac Telekom traje mjesec dana ima 3 punjaca od 4e',
            'contains' => ['Pregled ugovora prije kreiranja', 'HardNet DOO', 'Telekom CG', 'punjaca: 3 x 4 EUR', 'Ukupno za plaćanje: 14.52 EUR'],
        ]], $provider);
    }

    private function scenarioCurrencyVariants(string $provider): array
    {
        return $this->runScenario('currency variants', [[
            'message' => 'Napravi ugovor firma HardNet kupac Telekom traje mjesec dana ima 2 kabla po 4 EUR i 1 mis za 5€',
            'contains' => ['Pregled ugovora prije kreiranja', 'kabla: 2 x 4 EUR', 'mis: 1 x 5 EUR'],
        ]], $provider);
    }

    private function scenarioDraftCompletion(string $provider): array
    {
        return $this->runScenario('multi-step draft completion', [
            [
                'message' => 'Napravi ugovor firma HardNet kupac Telekom ima 3 punjaca',
                'contains' => ['Imam:', 'Fali:', 'period ugovora', 'cijena za novu stavku'],
            ],
            [
                'message' => 'punjac je 5e',
                'contains' => ['Imam:', '3 x 5 EUR', 'period ugovora'],
            ],
            [
                'message' => 'traje mjesec dana',
                'contains' => ['Pregled ugovora prije kreiranja', '3 x 5 EUR', 'Ukupno za plaćanje: 18.15 EUR'],
            ],
        ], $provider);
    }

    private function scenarioPreviewEdits(string $provider): array
    {
        return $this->runScenario('preview edits', [
            [
                'message' => 'Napravi ugovor firma HardNet kupac Telekom traje mjesec dana ima 3 stolice po 80e i 2 carape po 2e',
                'contains' => ['Pregled ugovora prije kreiranja', 'Stolice: 3 x 80 EUR', 'carape: 2 x 2 EUR'],
            ],
            [
                'message' => 'ukloni carape i ne 3 stolice nego 5 i stolica nije 80 nego 60',
                'contains' => ['Izmijenio sam preview ugovora', 'stolice: 5 x 60 EUR'],
                'not_contains' => ['carape'],
            ],
        ], $provider);
    }

    private function runScenario(string $name, array $steps, string $provider): array
    {
        $startedAt = microtime(true);
        $errors = [];
        $lastResponse = '';
        $session = new Store('contract-matrix-' . md5($name), new ArraySessionHandler(120));
        $controller = new ChatController();

        session()->forget(['chat_draft_action', 'pending_chat_action', 'chat_context', 'current_ai_entities']);

        foreach ($steps as $step) {
            $request = Request::create('/chat/stream', 'POST', [
                'message' => $step['message'],
                'provider' => $provider,
                'history' => [],
            ]);
            $request->setLaravelSession($session);
            $response = $controller->stream($request);
            $payload = $response->getData(true);
            $lastResponse = (string) ($payload['response'] ?? '');

            foreach ($step['contains'] ?? [] as $needle) {
                if (!$this->containsText($lastResponse, $needle)) {
                    $errors[] = "Expected response to contain [{$needle}] after message [{$step['message']}].";
                }
            }

            foreach ($step['not_contains'] ?? [] as $needle) {
                if ($this->containsText($lastResponse, $needle)) {
                    $errors[] = "Expected response not to contain [{$needle}] after message [{$step['message']}].";
                }
            }
        }

        return [
            'name' => $name,
            'passed' => $errors === [],
            'errors' => $errors,
            'last_response' => $lastResponse,
            'time' => round(microtime(true) - $startedAt, 2),
        ];
    }

    private function containsText(string $haystack, string $needle): bool
    {
        return str_contains($this->normalizeText($haystack), $this->normalizeText($needle));
    }

    private function normalizeText(string $text): string
    {
        $text = mb_strtolower($text);
        $text = strtr($text, [
            'č' => 'c',
            'ć' => 'c',
            'š' => 's',
            'đ' => 'dj',
            'ž' => 'z',
        ]);

        return preg_replace('/\s+/', ' ', $text) ?? $text;
    }
}
