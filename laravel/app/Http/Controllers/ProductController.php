<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function ajaxStore(Request $request)
{
    $request->validate([
        'name' => 'required|string|unique:products,name',
        'unit' => 'nullable|string',
        'vat_rate_id' => 'required|exists:vat_rates,id',
    ]);

    $product = Product::create([
        'name' => $request->name,
        'unit' => $request->unit ?? 'kom',
        'vat_rate_id' => $request->vat_rate_id,
        'price' => $request->price ?? 0
    ]);

    return response()->json([
        'success' => true,
        'product' => $product,
    ]);
}

}
