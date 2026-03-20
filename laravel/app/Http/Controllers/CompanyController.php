<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Company;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = Company::orderBy('id', 'desc')->get();
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
{
    $request->validate([
        'name'                => 'required|string|max:255',
        'tax_id_type'         => 'required|string',
        'tax_id_number'       => 'required|string|max:100',
        'country'             => 'required|string|max:100',
        'city'                => 'required|string|max:100',
        'address'             => 'required|string|max:255',
        'enu_code'            => 'required|string|max:50',
        'business_unit_code'  => 'required|string|max:50',
        'software_code'       => 'required|string|max:50',
        'bank_account_number' => 'nullable|string|max:50',
        'is_issuer_in_vat'    => 'nullable',
    ]);

    $company = Company::create([
        'name'                => $request->name,
        'tax_id_type'         => $request->tax_id_type,
        'tax_id_number'       => $request->tax_id_number,
        'country'             => $request->country,
        'city'                => $request->city,
        'address'             => $request->address,
        'enu_code'            => $request->enu_code,
        'business_unit_code'  => $request->business_unit_code,
        'software_code'       => $request->software_code,
        'bank_account_number' => $request->bank_account_number,
        'is_issuer_in_vat'    => $request->has('is_issuer_in_vat'),
    ]);

    // Sačuvaj operatore
    $operators = json_decode($request->operators_data, true) ?? [];
    foreach ($operators as $op) {
        \App\Models\User::create([
            'company_id'    => $company->id,
            'name'          => $op['name'],
            'email'         => $op['email'],
            'operator_code' => $op['operator_code'],
            'role'          => $op['role'],
            'is_active'     => $op['is_active'],
        ]);
    }

    return redirect()->route('companies.index')->with('success', 'Kompanija uspješno dodana.');
}

    public function edit($id)
    {
        $company = Company::with('users')->findOrFail($id);
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, $id)
    {
        $company = Company::findOrFail($id);

        $request->validate([
            'name'                => 'required|string|max:255',
            'tax_id_type'         => 'required|string',
            'tax_id_number'       => 'required|string|max:100',
            'country'             => 'required|string|max:100',
            'city'                => 'required|string|max:100',
            'address'             => 'required|string|max:255',
            'enu_code'            => 'required|string|max:50',
            'business_unit_code'  => 'required|string|max:50',
            'software_code'       => 'required|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',
            'is_issuer_in_vat'    => 'nullable',
        ]);

        $company->update([
            'name'                => $request->name,
            'tax_id_type'         => $request->tax_id_type,
            'tax_id_number'       => $request->tax_id_number,
            'country'             => $request->country,
            'city'                => $request->city,
            'address'             => $request->address,
            'enu_code'            => $request->enu_code,
            'business_unit_code'  => $request->business_unit_code,
            'software_code'       => $request->software_code,
            'bank_account_number' => $request->bank_account_number,
            'is_issuer_in_vat' => $request->has('is_issuer_in_vat'),
        ]);

        return redirect()->route('companies.index')->with('success', 'Kompanija uspješno izmijenjena.');
    }
}