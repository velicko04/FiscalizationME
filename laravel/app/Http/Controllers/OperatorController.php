<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class OperatorController extends Controller
{
    public function store(Request $request, $companyId)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'operator_code' => 'required|string|max:50',
            'role'          => 'required|string|max:50',
            'is_active'     => 'nullable',
        ]);

        User::create([
            'company_id'    => $companyId,
            'name'          => $request->name,
            'email'         => $request->email,
            'operator_code' => $request->operator_code,
            'role'          => $request->role,
            'is_active'     => $request->has('is_active'),
        ]);

        return redirect()->route('companies.edit', $companyId)->with('success', 'Operator uspješno dodan.');
    }

    public function update(Request $request, $id)
    {
        $operator = User::findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'operator_code' => 'required|string|max:50',
            'role'          => 'required|string|max:50',
            'is_active'     => 'nullable',
        ]);

        $operator->update([
            'name'          => $request->name,
            'email'         => $request->email,
            'operator_code' => $request->operator_code,
            'role'          => $request->role,
            'is_active'     => $request->has('is_active'),
        ]);

        return redirect()->route('companies.edit', $operator->company_id)->with('success', 'Operator uspješno izmijenjen.');
    }

    public function destroy($id)
    {
        $operator = User::findOrFail($id);
        $companyId = $operator->company_id;
        $operator->delete();

        return redirect()->route('companies.edit', $companyId)->with('success', 'Operator uspješno obrisan.');
    }
}