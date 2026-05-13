<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Chat\ChatContractCreationTrait;
use App\Http\Controllers\Chat\ChatInvoiceCreationTrait;
use App\Http\Controllers\Chat\ChatLlmProvidersTrait;
use App\Http\Controllers\Chat\ChatMemoryTrait;
use App\Http\Controllers\Chat\ChatPendingActionsTrait;
use App\Http\Controllers\Chat\ChatPromptDataTrait;
use App\Http\Controllers\Chat\ChatQueryActionsTrait;
use App\Http\Controllers\Chat\ChatRoutingTrait;

class ChatController extends Controller
{
    use ChatContractCreationTrait;
    use ChatInvoiceCreationTrait;
    use ChatLlmProvidersTrait;
    use ChatMemoryTrait;
    use ChatPendingActionsTrait;
    use ChatPromptDataTrait;
    use ChatQueryActionsTrait;
    use ChatRoutingTrait;

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

        if ($this->isDraftCancelResponse($message)) {
            session()->forget('chat_draft_action');

            return response()->json([
                'response' => 'U redu, obrisao sam nacrt. Možeš krenuti ponovo kad budeš spreman.',
                'stats' => ['time_s' => 0, 'provider' => $provider, 'request_id' => $requestId, 'action' => 'draft_cancelled'],
            ]);
        }

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

            $payload = is_array($content) ? $content : ['response' => $content];

            return response()->json(array_merge($payload, [
                'stats' => ['time_s' => $elapsed, 'provider' => $provider, 'request_id' => $requestId, 'action' => 'pending_action_response']
            ]));
        }

        if (session()->has('pending_chat_action')) {
            $startTime = microtime(true);
            $content = $this->handlePendingActionModification($request, $message, $provider, $requestId);

            if (is_array($content)) {
                $this->rememberToolResult('pending_action_edit', $content['response'] ?? '');
                return $this->chatJsonResponse($content['response'], $provider, $requestId, 'pending_action_edit', $startTime, $content);
            }

            $this->rememberToolResult('pending_action_edit', $content);
            return $this->chatJsonResponse($content, $provider, $requestId, 'pending_action_edit', $startTime);
        }

        return $this->handleAiRoutedMessage($request, $message, $history, $provider, $requestId);
    }
}
