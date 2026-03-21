<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Company;
use App\Models\Buyer;
use App\Models\Product;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    // LISTA UGOVORA
    public function index(Request $request)
    {
        $query = Contract::with([
            'company',
            'buyer',
            'items.product.vatRate'
        ])->withCount('invoices');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('start_date', [$request->from, $request->to]);
        } elseif ($request->filled('range')) {
            $query->where('start_date', '>=', now()->subDays($request->range));
        }

        $contracts = $query->orderBy('id', 'desc')->get();

        return view('contracts.index', compact('contracts'));
    }

    // CREATE FORM
public function create()
{
    $companies = Company::all();
    $buyers = Buyer::all();
    $products = Product::with('vatRate')->get()->map(function($p){
        return [
            'id' => $p->id,
            'name' => $p->name,
            'price' => $p->price,
            'vat_rate_id' => $p->vat_rate_id,
            'vatRate' => ['percentage' => $p->vatRate->percentage ?? 0]
        ];
    });

    return view('contracts.create', compact('companies', 'buyers', 'products'));
}

    // INVOICES PO UGOVORU
    public function invoices($id, Request $request)
{
    $contract = Contract::with('company', 'buyer')->findOrFail($id);

    $query = Invoice::where('contract_id', $id)
        ->with('company', 'buyer', 'user', 'items.product.vatRate', 'correctiveInvoices');

    // Filter po range
    if ($request->filled('range')) {
        $query->where('issued_at', '>=', now()->subDays($request->range));
    }

    // Filter po datumu od-do
    if ($request->filled('from')) {
        $query->where('issued_at', '>=', $request->from);
    }

    if ($request->filled('to')) {
        $query->where('issued_at', '<=', $request->to . ' 23:59:59');
    }

    // Najnoviji prvi
    $invoices = $query->orderBy('issued_at', 'desc')->get();

    return view('contracts.invoices', compact('contract', 'invoices'));
}


    // STORE UGOVORA
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
        'items_data' => 'required|string',
    ]);

    $items = json_decode($request->items_data, true);

    DB::transaction(function () use ($request, $items) {
        // Kreiranje ugovora
        $contract = Contract::create([
            'contract_number' => $request->contract_number,
            'company_id' => $request->company_id,
            'buyer_id' => $request->buyer_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'billing_frequency' => $request->billing_frequency,
            'issue_day' => $request->issue_day,
            'status' => $request->status,
            'default_type_of_invoice' => $request->default_type_of_invoice,
            'default_payment_method'  => $request->default_payment_method,
        ]);

        foreach ($items as $item) {
            // Provjera ako proizvod već postoji, inače ga kreiraj
            $product = Product::updateOrCreate(
                ['name' => $item['name']],  // provjeri po imenu
                [
                    'price' => $item['price'],  // postavi cijenu iz forme
                    'vat_rate_id' => $item['vat_rate_id'] ?? 1,  // postavi default VAT ako nije prisutan
                    'unit' => 'kom',
                ]
            );

            // Kreiranje stavke ugovora
            $contract->items()->create([
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],  // koristi cijenu iz forme
                'vat_rate_id' => $product->vat_rate_id, // koristi VAT iz proizvoda
            ]);
        }
    });

    return redirect()->route('contracts.index')->with('success', 'Ugovor je uspješno dodat!');
}


  // EDIT
    public function edit($id)
    {
        $contract = Contract::with('items.product.vatRate', 'invoices')->findOrFail($id);

        $companies = Company::all();
        $buyers = Buyer::all();
        $products = Product::with('vatRate')->get()->map(function($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'vat_rate_id' => $p->vat_rate_id,
                'vatRate' => ['percentage' => $p->vatRate->percentage ?? 0]
            ];
        });

        return view('contracts.edit', compact('contract', 'companies', 'buyers', 'products'));
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $contract = Contract::with('invoices')->findOrFail($id);

        $request->validate([
            'company_id'        => 'required|exists:Company,id',
            'buyer_id'          => 'required|exists:Buyer,id',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
            'billing_frequency' => 'required',
            'issue_day'         => 'required|integer|min:1|max:31',
            'status'            => 'required',
            'items_data'        => 'required|string',
        ]);

        $items = json_decode($request->items_data, true);

        DB::transaction(function () use ($request, $contract, $items) {

            $contract->update([
                'company_id'        => $request->company_id,
                'buyer_id'          => $request->buyer_id,
                'start_date'        => $request->start_date,
                'end_date'          => $request->end_date,
                'billing_frequency' => $request->billing_frequency,
                'issue_day'         => $request->issue_day,
                'status'            => $request->status,
            ]);

            $contract->items()->delete();

            foreach ($items as $item) {
                $product = Product::updateOrCreate(
                    ['name' => $item['name']],
                    [
                        'price'      => $item['price'],
                        'vat_rate_id' => $item['vat_rate_id'] ?? 1,
                        'unit'       => 'kom',
                    ]
                );

                $contract->items()->create([
                    'product_id'  => $product->id,
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['price'],
                    'vat_rate_id' => $product->vat_rate_id,
                ]);
            }
        });

        return redirect()->route('contracts.index')
            ->with('success', 'Ugovor uspješno izmijenjen.');
    }
}
