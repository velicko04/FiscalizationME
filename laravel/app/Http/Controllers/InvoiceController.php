<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceController extends Controller
{
    // Lista svih računa
    public function index()
    {
        // Učitavamo račune zajedno sa proizvodima, kompanijom, kupcem i korisnikom
        $invoices = Invoice::with('items.product.vatRate', 'company', 'buyer', 'user')->get();

        // Izračun ukupnog iznosa i PDV-a za svaki račun
        $invoices->map(function($invoice) {
            $total = 0;
            $totalVat = 0;

            foreach ($invoice->items as $item) {
                $price = $item->quantity * $item->unit_price;
                $vatRate = $item->product->vatRate 
                    ? (float)$item->product->vatRate->percentage 
                    : 0;

                $total += $price * (1 + $vatRate / 100);
                $totalVat += $price * ($vatRate / 100);
            }

            $invoice->total_price_to_pay = $total;
            $invoice->total_vat_amount = $totalVat;

            return $invoice;
        });

        return view('invoices.index', compact('invoices'));
    }

    public function show($id)
{
    $invoice = Invoice::with(
        'items.product.vatRate',
        'company',
        'buyer',
        'contract'
    )->findOrFail($id);

    return view('invoices.show', compact('invoice'));
}

}
