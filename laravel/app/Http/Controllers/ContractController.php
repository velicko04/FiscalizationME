<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Company;
use App\Models\Buyer;
use App\Models\Product;
use App\Models\ContractItem;
use App\Models\Invoice;

class ContractController extends Controller
{
    // Lista svih ugovora
public function index(Request $request)
{
    $query = Contract::with(['company', 'buyer', 'items.product.vatRate']);

    // STATUS FILTER
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // LAST X DAYS FILTER
    if ($request->filled('range')) {
        $query->where('start_date', '>=', now()->subDays($request->range));
    }

    // CUSTOM DATE RANGE FILTER
    if ($request->filled('from') && $request->filled('to')) {
        $query->whereBetween('start_date', [$request->from, $request->to]);
    }

    $contracts = $query->orderBy('start_date', 'desc')->get();

    return view('contracts.index', compact('contracts'));
}


public function invoices($id)
{
    $contract = Contract::with('company', 'buyer')->findOrFail($id);

    $invoices = Invoice::where('contract_id', $id)
    ->with('company', 'buyer', 'user', 'items.product.vatRate')
    ->get();

    return view('contracts.invoices', compact('contract', 'invoices'));
}



    public function create()
{
    $companies = Company::all();
    $buyers = Buyer::all();
    $products = Product::with('vatRate')->get();

    return view('contracts.create', compact('companies', 'buyers', 'products'));
}

public function store(Request $request)
{
    $request->validate([
        'contract_number' => 'required|unique:Contract,contract_number',
        'company_id' => 'required|exists:Company,id',
        'buyer_id' => 'required|exists:Buyer,id',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'billing_frequency' => 'required',
        'issue_day' => 'required|integer|min:1|max:31',
        'status' => 'required',
        'items.*.product_id' => 'required|exists:Product,id',
        'items.*.quantity' => 'required|numeric|min:0.01',
        'items.*.unit_price' => 'required|numeric|min:0.01',
    ]);

    $contract = Contract::create([
        'contract_number' => $request->contract_number,
        'company_id' => $request->company_id,
        'buyer_id' => $request->buyer_id,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'billing_frequency' => $request->billing_frequency,
        'issue_day' => $request->issue_day,
        'status' => $request->status,
        'created_at' => now()
    ]);

    foreach ($request->items as $item) {
        $product = Product::find($item['product_id']);

        $contract->items()->create([
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'vat_rate_id' => $product->vat_rate_id,
        ]);
    }

    return redirect()->route('contracts.index')
        ->with('success', 'Ugovor je uspješno dodat!');
}

public function edit($id)
{
    $contract = Contract::with('items')->findOrFail($id);
    $products = Product::all();

    return view('contracts.edit', compact('contract', 'products'));
}


public function update(Request $request, $id)
{
    $contract = Contract::findOrFail($id);

    $contract->update([
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'billing_frequency' => $request->billing_frequency,
        'issue_day' => $request->issue_day,
        'status' => $request->status,
    ]);

    // OBRIŠI stare stavke
    $contract->items()->delete();

    // Dodaj nove
    if ($request->products) {
        foreach ($request->products as $index => $productId) {

            ContractItem::create([
                'contract_id' => $contract->id,
                'product_id' => $productId,
                'quantity' => $request->quantities[$index],
                'unit_price' => $request->prices[$index],
                'vat_rate_id' => Product::find($productId)->vat_rate_id
            ]);
        }
    }

    return redirect()->route('contracts.index')
        ->with('success', 'Ugovor uspješno izmijenjen.');
}


}
