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
    $status = $request->query('status'); // npr. ?status=active

    $query = Contract::with('items.product.vatRate', 'company', 'buyer');

    if ($status) {
        $query->where('status', $status);
    }

    $contracts = $query->get()->map(function($contract) {
        $total = 0;
        foreach ($contract->items as $item) {
            // pazimo da quantity i unit_price budu float
            $quantity = (float) $item->quantity;
            $unit_price = (float) $item->unit_price;
            $vat = $item->product->vatRate ? (float)$item->product->vatRate->percentage : 0;
            $total += $quantity * $unit_price * (1 + $vat / 100);
        }
        $contract->total_amount = $total;
        return $contract;
    });

    return view('contracts.index', compact('contracts', 'status'));
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

}
