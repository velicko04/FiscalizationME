<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $table = 'Contract';

    public $timestamps = false;

    protected $fillable = [
        'contract_number',
        'company_id',
        'buyer_id',
        'start_date',
        'end_date',
        'billing_frequency',
        'issue_day',
        'status',
        'created_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class, 'buyer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'contract_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'contract_id');
    }

    public function getTotalAmountAttribute()
{
    $total = 0;

    foreach ($this->items as $item) {

        $vatPercentage = 0;

        if ($item->product && $item->product->vatRate) {
            $vatPercentage = $item->product->vatRate->percentage;
        }

        $base = $item->unit_price * $item->quantity;
        $vatAmount = $base * ($vatPercentage / 100);

        $total += $base + $vatAmount;
    }

    return $total;
}

}


