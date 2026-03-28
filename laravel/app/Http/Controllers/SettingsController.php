<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        return view('settings.index', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Trenutna lozinka nije ispravna.']);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Lozinka uspješno promijenjena.');
    }

 public function notifications()
{
    $notifications = collect();

    // Ugovori koji ističu za 30 dana
    $expiringContracts = \App\Models\Contract::where('status', 'active')
        ->where('end_date', '<=', now()->addDays(30))
        ->where('end_date', '>=', now())
        ->get();

    foreach ($expiringContracts as $contract) {
        $daysLeft = now()->diffInDays($contract->end_date);
        $notifications->push([
            'icon'  => '⚠️',
            'type'  => 'warning',
            'title' => 'Contract expiring soon',
            'text'  => $contract->contract_number . ' ističe za ' . $daysLeft . ' dana',
            'url'   => '/contracts/' . $contract->id . '/invoices',
            'time'  => $contract->end_date,
        ]);
    }

    // Fakture sa greškama — nefiskalizovane koje imaju ERROR log
    $failedLogs = \App\Models\FiscalLog::where('status', 'ERROR')
        ->with('invoice')
        ->latest()
        ->take(10)
        ->get();

    foreach ($failedLogs as $log) {
        if ($log->invoice && !$log->invoice->fic) {
            $notifications->push([
                'icon'  => '❌',
                'type'  => 'error',
                'title' => 'Greška fiskalizacije',
                'text'  => $log->invoice->invoice_number,
                'url'   => '/fiscal-logs',
                'time'  => $log->created_at,
            ]);
        }
    }

    return response()->json(
        $notifications->sortByDesc('time')->values()
    );
}


    public function search(Request $request)
{
    $q = $request->get('q', '');

    $contracts = \App\Models\Contract::with('buyer')
        ->where('contract_number', 'like', "%{$q}%")
        ->orWhereHas('buyer', fn($query) => $query->where('name', 'like', "%{$q}%"))
        ->limit(5)->get()
        ->map(fn($c) => [
            'id' => $c->id,
            'contract_number' => $c->contract_number,
            'buyer' => $c->buyer->name,
            'status' => $c->status,
        ]);

    $invoices = \App\Models\Invoice::with('buyer')
        ->where('invoice_number', 'like', "%{$q}%")
        ->orWhereHas('buyer', fn($query) => $query->where('name', 'like', "%{$q}%"))
        ->limit(5)->get()
        ->map(fn($i) => [
            'id' => $i->id,
            'invoice_number' => $i->invoice_number,
            'buyer' => $i->buyer->name,
            'total' => number_format($i->total_price_to_pay, 2),
            'contract_id' => $i->contract_id,
        ]);

    return response()->json(['contracts' => $contracts, 'invoices' => $invoices]);
}
}