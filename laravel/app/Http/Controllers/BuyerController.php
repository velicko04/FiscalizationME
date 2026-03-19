<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Buyer;
use App\Enums\TaxIdType;

class BuyerController extends Controller
{
    public function ajaxStore(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'tax_id_type'   => 'required|string',
            'tax_id_number' => 'required|string|max:100',
            'country'       => 'required|string|max:100',
            'city'          => 'required|string|max:100',
            'address'       => 'required|string|max:255',
        ]);

        $buyer = Buyer::create([
            'name'          => $request->name,
            'tax_id_type'   => $request->tax_id_type,
            'tax_id_number' => $request->tax_id_number,
            'country'       => $request->country,
            'city'          => $request->city,
            'address'       => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'buyer'   => [
                'id'   => $buyer->id,
                'name' => $buyer->name,
            ]
        ]);
    }
}