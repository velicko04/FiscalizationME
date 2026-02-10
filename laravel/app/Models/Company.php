<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\TaxIdType;

class Company extends Model{

    protected $table = 'Company';

    public $timestamps = false;

    protected $fillable = [

        'tax_id_type',
        'tax_id_number',
        'name',
        'country',
        'city',
        'address',
        'enu_code',
        'business_unit_code',
        'software_code',
        'bank_account_number',
        'is_issuer_in_vat',
    ];

    protected $casts = [
        'is_issuer_in_vat' => 'boolean',
        'tax_id_type' => TaxIdType::class,
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'company_id');
    }

}

?>