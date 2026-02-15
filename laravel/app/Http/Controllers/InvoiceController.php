<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class InvoiceController extends Controller
{
    // Lista svih računa
    public function index(Request $request)
{
    $query = Invoice::with([
        'company',
        'buyer',
        'user',
        'items.product.vatRate'
    ]);

    // LAST X DAYS FILTER
    if ($request->filled('range')) {
        $query->where('issued_at', '>=', now()->subDays($request->range));
    }

    // CUSTOM DATE RANGE
    if ($request->filled('from') && $request->filled('to')) {
        $query->whereBetween('issued_at', [$request->from, $request->to]);
    }

    $invoices = $query->orderBy('issued_at', 'desc')->get();

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
