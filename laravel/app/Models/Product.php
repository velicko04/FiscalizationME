<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'Product';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'vat_rate_id',
        'price',
    ];

    public function vatRate(): BelongsTo
    {
        return $this->belongsTo(VatRate::class, 'vat_rate_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'product_id');
    }

    public function contractItems(): HasMany
    {
        return $this->hasMany(ContractItem::class, 'product_id');
    }
}
