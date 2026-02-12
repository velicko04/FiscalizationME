<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;

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
            $vatRate = $item->product->vatRate ? (float)$item->product->vatRate->percentage : 0;

            $total += $price * (1 + $vatRate / 100);
            $totalVat += $price * ($vatRate / 100);
        }

        $invoice->total_price_to_pay = $total;
        $invoice->total_vat_amount = $totalVat;

        return $invoice;
    });

    return view('invoices.index', compact('invoices'));
}

// Prikaz forme za dodavanje novog računa
public function create()
{
    // Ako želiš da biraš kompaniju, kupca i korisnika u formi:
    $companies = \App\Models\Company::all();
    $buyers = \App\Models\Buyer::all();
    $users = \App\Models\User::all();
    $products = \App\Models\Product::all();

    return view('invoices.create', compact('companies', 'buyers', 'users', 'products'));
}

public function store(Request $request)
{
    // Validacija osnovnih polja
    $request->validate([
        'invoice_number' => 'required|unique:Invoice,invoice_number',
        'company_id' => 'required|exists:Company,id',
        'buyer_id' => 'required|exists:Buyer,id',
        'user_id' => 'required|exists:User,id',
        'issued_at' => 'required|date',
        'payment_method_type' => 'required',
        'items.*.product_id' => 'required|exists:Product,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0.01',
    ]);

    // Izračun ukupnog iznosa i PDV-a
    $totalWithoutVat = 0;
    $totalVat = 0;

    foreach($request->items as $item) {
        $product = Product::find($item['product_id']);
        $quantity = $item['quantity'];
        $unitPrice = $item['unit_price'];
        $vatRate = $product->vatRate ? (float)$product->vatRate->percentage : 0;

        $totalWithoutVat += $quantity * $unitPrice;
        $totalVat += $quantity * $unitPrice * ($vatRate / 100);
    }

    $totalWithVat = $totalWithoutVat + $totalVat;

    // Kreiranje računa
    $invoice = Invoice::create([
        'invoice_number' => $request->invoice_number,
        'company_id' => $request->company_id,
        'buyer_id' => $request->buyer_id,
        'user_id' => $request->user_id,
        'issued_at' => $request->issued_at,
        'payment_method_type' => $request->payment_method_type,
        'total_price_without_vat' => $totalWithoutVat,
        'total_vat_amount' => $totalVat,
        'total_price_to_pay' => $totalWithVat,
    ]);

    // Dodavanje stavki
    foreach($request->items as $item) {
        $product = Product::find($item['product_id']);
        $invoice->items()->create([
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'vat_rate_id' => $product->vat_rate_id,
        ]);
    }

    return redirect()->route('invoices.index')->with('success', 'Račun je uspešno dodat!');
}

}