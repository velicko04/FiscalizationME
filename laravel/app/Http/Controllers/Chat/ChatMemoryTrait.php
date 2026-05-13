<?php

namespace App\Http\Controllers\Chat;

use Illuminate\Http\Request;

trait ChatMemoryTrait
{
    private function rememberChatContext($contract = null, $invoice = null): void
    {
        $context = session('chat_context', []);
    
        if ($contract) {
            $context['activeContractId'] = $contract->id;
            $context['last_contract_id'] = $contract->id;
            $context['last_contract_number'] = $contract->contract_number;
        }
    
        if ($invoice) {
            $context['activeInvoiceId'] = $invoice->id;
            $context['last_invoice_id'] = $invoice->id;
            $context['last_invoice_number'] = $invoice->invoice_number;
    
            if ($invoice->contract) {
                $context['activeContractId'] = $invoice->contract->id;
                $context['last_contract_id'] = $invoice->contract->id;
                $context['last_contract_number'] = $invoice->contract->contract_number;
            } elseif ($invoice->contract_id) {
                $context['activeContractId'] = $invoice->contract_id;
                $context['last_contract_id'] = $invoice->contract_id;
                $contractNumber = \App\Models\Contract::whereKey($invoice->contract_id)->value('contract_number');
                if ($contractNumber) {
                    $context['last_contract_number'] = $contractNumber;
                }
            }
        }
    
        if ($contract && $contract->company) {
            $context['activeCompany'] = $contract->company->name;
        } elseif ($invoice && $invoice->company) {
            $context['activeCompany'] = $invoice->company->name;
        }
    
        $context['updated_at'] = now()->toDateTimeString();
        session(['chat_context' => $context]);
    }
    
    private function rememberToolResult(string $intentName, string $response): void
    {
        $context = session('chat_context', []);
        $context['lastIntent'] = $intentName;
        $context['previousToolResults'] = array_slice(array_merge($context['previousToolResults'] ?? [], [[
            'intent' => $intentName,
            'summary' => mb_substr($response, 0, 500),
            'created_at' => now()->toDateTimeString(),
        ]]), -5);
    
        session(['chat_context' => $context]);
    }
    
    private function lastContextInvoice()
    {
        $invoiceId = session('chat_context.last_invoice_id');
        if (!$invoiceId) {
            return null;
        }
    
        return \App\Models\Invoice::with([
            'company',
            'buyer',
            'contract',
            'user',
            'items.product.vatRate',
            'items.vatRate',
        ])->find($invoiceId);
    }
    
    private function lastContextContract()
    {
        $contractNumber = session('chat_context.last_contract_number');
        if (!$contractNumber) {
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
    
    private function referencesPreviousInvoice(string $message): bool
    {
        $message = mb_strtolower($message);
    
        return str_contains($message, 'ta faktura')
            || str_contains($message, 'tu fakturu')
            || str_contains($message, 'te fakture')
            || str_contains($message, 'ovu fakturu')
            || str_contains($message, 'taj račun')
            || str_contains($message, 'taj racun')
            || str_contains($message, 'pdf')
            || str_contains($message, 'pošalji je')
            || str_contains($message, 'posalji je')
            || str_contains($message, 'pošalji tu')
            || str_contains($message, 'posalji tu');
    }
    
    private function referencesPreviousContract(string $message): bool
    {
        $message = mb_strtolower($message);
    
        return str_contains($message, 'taj ugovor')
            || str_contains($message, 'tog ugovora')
            || str_contains($message, 'tom ugovoru')
            || str_contains($message, 'ovaj ugovor')
            || str_contains($message, 'ovog ugovora')
            || str_contains($message, 'njemu');
    }
}
