<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VatRate extends Model
{
    protected $table = 'VatRate';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'percentage',
        'vat_exemption_reason',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vat_rate_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'vat_rate_id');
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'vat_rate_id');
    }
}
