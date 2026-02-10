<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $table = 'User';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'operator_code',
        'role',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'user_id');
    }
}
