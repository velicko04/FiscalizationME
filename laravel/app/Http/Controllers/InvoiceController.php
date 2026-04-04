<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FiscalLog;

class InvoiceController extends Controller
{
    // Lista svih računa
    public function index(Request $request)
{
    $query = Invoice::with([
        'company',
        'buyer',
        'user',
        'items.product.vatRate',
        'fiscalLogs'
    ]);

    // RANGE FILTER (All time, Last 7/30/90 days)
    if ($request->filled('range')) {
        $query->where('issued_at', '>=', now()->subDays($request->range));
    }

    // STATUS FILTER
    if ($request->filled('status')) {
        if ($request->status === 'fisk') {
            $query->whereNotNull('fic');
        } elseif ($request->status === 'nije_fisk') {
            $query->whereNull('fic')->where('invoice_type', '!=', 'CORRECTIVE');
        }
    }

    // SEARCH: broj računa, kompanija, buyer, seller
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('invoice_number', 'like', '%' . $search . '%')
              ->orWhereHas('company', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
              ->orWhereHas('buyer', fn($q) => $q->where('name', 'like', '%' . $search . '%'))
              ->orWhereHas('user', fn($q) => $q->where('name', 'like', '%' . $search . '%'));
        });
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

  public function logs(Request $request)
{
    $query = FiscalLog::with('invoice');

    // Filter samo po invoice_number
    if ($request->filled('invoice_number')) {
        $query->whereHas('invoice', function ($q) use ($request) {
            $q->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        });
    }

    $logs = $query->orderBy('created_at', 'desc')->get();

    return view('invoices.logs', compact('logs'));
}

public function invoiceLogs($id)
{
    $logs = FiscalLog::where('invoice_id', $id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json($logs);
}

public function pdf($id)
{
    $invoice = Invoice::with([
        'items.product.vatRate',
        'company',
        'buyer',
        'user'
    ])->findOrFail($id);

    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.pdf', compact('invoice'));
    $filename = 'faktura-' . preg_replace('/[^A-Za-z0-9\-]/', '-', $invoice->invoice_number) . '.pdf';

    return $pdf->download($filename);
}

}
