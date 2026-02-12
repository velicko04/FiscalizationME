<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;

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



    // Prikaz jednog ugovora
    public function show($id)
    {
        $contract = Contract::with('items.product.vatRate', 'company', 'buyer')->findOrFail($id);

        return view('contracts.show', compact('contract'));
    }
}
