<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\CorrectiveInvoice;

class StornoController extends Controller
{
    public function store($id)
    {
        $original = Invoice::with('items.product.vatRate', 'company', 'buyer', 'user')->findOrFail($id);

        // Provjeri da li je originalni račun fiskalizovan
        if (!$original->fic) {
            return response()->json([
                'status' => 400,
                'body' => 'Račun mora biti fiskalizovan prije storniranja.'
            ]);
        }

        // Provjeri da li već postoji storno
        $alreadyStorno = CorrectiveInvoice::where('invoice_id', $original->id)
            ->where('type', 'CORRECTION')
            ->exists();

        if ($alreadyStorno) {
            return response()->json([
                'status' => 400,
                'body' => 'Ovaj račun je već storniran.'
            ]);
        }

        $year = now()->year;
        $orderNumber = Invoice::whereYear('issued_at', $year)->count() + 1;
        $buCode = strtolower($original->company->business_unit_code ?? '');
        $enuCode = strtolower($original->company->enu_code ?? '');
        $invoiceNumber = "{$orderNumber}/{$year}/{$buCode}/{$enuCode}";

        // Kreiraj storno račun sa negativnim iznosima
        $storno = Invoice::create([
            'invoice_number'          => $invoiceNumber,
            'order_number'            => $orderNumber,
            'invoice_type'            => 'CORRECTIVE',
            'type_of_invoice'         => $original->type_of_invoice->value,
            'issued_at'               => now(),
            'tax_period'              => now()->format('m/Y'),
            'total_price_without_vat' => -abs($original->total_price_without_vat),
            'payment_method_type'     => $original->payment_method_type->value,
            'total_vat_amount'        => -abs($original->total_vat_amount),
            'total_price_to_pay'      => -abs($original->total_price_to_pay),
            'note'                    => 'Storno računa: ' . $original->invoice_number,
            'company_id'              => $original->company_id,
            'buyer_id'                => $original->buyer_id,
            'user_id'                 => $original->user_id,
            'contract_id'             => $original->contract_id,
            'created_at'              => now(),
        ]);

        // Kopiraj stavke sa negativnim količinama
        foreach ($original->items as $item) {
            InvoiceItem::create([
                'invoice_id'  => $storno->id,
                'product_id'  => $item->product_id,
                'quantity'    => -abs($item->quantity),
                'unit_price'  => $item->unit_price,
                'vat_rate_id' => $item->vat_rate_id,
            ]);
        }

        // Kreiraj CorrectiveInvoice zapis
        CorrectiveInvoice::create([
            'invoice_id'             => $storno->id,
            'type'                   => 'CORRECTION',
            'reference_iic'          => $original->iic,
            'original_issue_datetime' => $original->issued_at,
        ]);

        return response()->json([
            'status' => 200,
            'body'   => 'Storno račun uspješno kreiran: ' . $invoiceNumber
        ]);
    }
}