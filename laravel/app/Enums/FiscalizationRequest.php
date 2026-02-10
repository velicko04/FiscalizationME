<?php

namespace App\Models\Fiscalization;

use App\Models\InvoiceDTO;

class FiscalizationRequest
{
    public string $id;
    public int $version;
    public FiscalizationHeader $header;
    public CreateInvoiceRequest $invoice;

    public function __construct(InvoiceDTO $invoice, int $version = 1)
    {
        $this->id = (string) \Illuminate\Support\Str::uuid();
        $this->version = $version;
        $this->header = new FiscalizationHeader();
        $this->invoice = $invoice;
    }
}

?>