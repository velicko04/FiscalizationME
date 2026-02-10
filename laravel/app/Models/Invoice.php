<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\InvoiceType;
use App\Enums\TypeOfInvoice;
use App\Enums\PaymentMethodType;

class Invoice extends Model
{
    protected $table = 'Invoice';

    public $timestamps = false;

    protected $fillable = [
        'invoice_number',
        'order_number',
        'invoice_type',
        'type_of_invoice',
        'issued_at',
        'tax_period',
        'total_price_without_vat',
        'payment_method_type',
        'total_vat_amount',
        'total_price_to_pay',
        'note',
        'payment_deadline',
        'iic',
        'iic_signature',
        'company_id',
        'buyer_id',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'created_at' => 'datetime',
        'total_price_without_vat' => 'decimal:2',
        'total_vat_amount' => 'decimal:2',
        'total_price_to_pay' => 'decimal:2',
        'invoice_type' => InvoiceType::class,
        'type_of_invoice' => TypeOfInvoice::class,
        'payment_method_type' => PaymentMethodType::class,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class, 'buyer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function correctiveInvoices(): HasMany
    {
        return $this->hasMany(CorrectiveInvoice::class, 'invoice_id');
    }
}
