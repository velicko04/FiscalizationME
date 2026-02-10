<?php

namespace App\DTO;

use App\Models\Product;
use App\Models\VatRate;

class InvoiceItemDTO
{
    public ?string $code = null;
    public string $name;
    public string $unit;
    public float $unitPrice;
    public float $quantity;
    public float $unitPriceAfterVat;
    public float $totalPriceBeforeVat;
    public float $totalPriceAfterVat;
    public float $vatRate;
    public float $vatAmount;
    public ?string $vatExemptionReason = null;
}

?>