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

        if ($request->filled('range')) {
            $query->where('start_date', '>=', now()->subDays($request->range));
        }

        if ($request->filled('from') && $request->filled('to')) {
            $query->whereBetween('start_date', [$request->from, $request->to]);
        }

        $contracts = $query->orderBy('start_date', 'desc')->get();

        return view('contracts.index', compact('contracts'));
    }

    // INVOICES PO UGOVORU
    public function invoices($id)
    {
        $contract = Contract::with('company', 'buyer')->findOrFail($id);

        $invoices = Invoice::where('contract_id', $id)
            ->with('company', 'buyer', 'user', 'items.product.vatRate')
            ->get();

        return view('contracts.invoices', compact('contract', 'invoices'));
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
            'vatRate' => ['percentage' => $p->vatRate->percentage ?? 0]
        ];
    });

    return view('contracts.create', compact('companies', 'buyers', 'products'));
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

        if ($contract->invoices()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'Ugovor se ne može mijenjati jer ima izdate račune.');
        }

        $products = Product::with('vatRate')->get();

        return view('contracts.edit', compact('contract', 'products'));
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $contract = Contract::with('invoices')->findOrFail($id);

        if ($contract->invoices()->exists()) {
            return redirect()->route('contracts.index')
                ->with('error', 'Ugovor se ne može izmijeniti jer ima izdate račune.');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'billing_frequency' => 'required',
            'issue_day' => 'required|integer|min:1|max:31',
            'status' => 'required',
            'products' => 'required|array|min:1',
            'products.*' => 'exists:Product,id',
            'quantities.*' => 'required|numeric|min:1',
        ]);

        DB::transaction(function () use ($request, $contract) {

            $contract->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'billing_frequency' => $request->billing_frequency,
                'issue_day' => $request->issue_day,
                'status' => $request->status,
            ]);

            // Obriši stare stavke
            $contract->items()->delete();

            // Dodaj nove
            foreach ($request->products as $index => $productId) {

                $product = Product::findOrFail($productId);

                $contract->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $request->quantities[$index] ?? 1,
                    'unit_price' => $product->price, // 🔒 sigurnosno iz baze
                    'vat_rate_id' => $product->vat_rate_id,
                ]);
            }
        });

        return redirect()->route('contracts.index')
            ->with('success', 'Ugovor uspješno izmijenjen.');
    }
}
