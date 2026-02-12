<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\TaxIdType;

class Buyer extends Model
{
    protected $table = 'Buyer';

    public $timestamps = false;

    protected $fillable = [
        'tax_id_type',
        'tax_id_number',
        'name',
        'country',
        'city',
        'address',
    ];

    protected $casts = [
    'tax_id_type' => TaxIdType::class,
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'buyer_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'buyer_id');
    }
}
