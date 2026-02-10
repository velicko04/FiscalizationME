<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\CorrectiveInvoiceType;

class CorrectiveInvoice extends Model
{
    protected $table = 'CorrectiveInvoice';

    public $timestamps = false;

    protected $fillable = [
        'invoice_id',
        'type',
        'reference_iic',
        'original_issue_datetime',
    ];

    protected $casts = [
        'original_issue_datetime' => 'datetime',
        'type' => CorrectiveInvoiceType::class,
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
