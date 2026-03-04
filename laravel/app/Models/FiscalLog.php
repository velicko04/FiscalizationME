<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;

class FiscalLog extends Model
{
    protected $fillable = [
        'invoice_id',
        'request_xml',
        'response_xml',
        'status',
        'error_message',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}