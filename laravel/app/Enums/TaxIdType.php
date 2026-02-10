<?php

namespace App\Enums;

enum TaxIdType: string
{
    case TIN = 'TIN';
    case PASS = 'PASS';
    case VAT = 'VAT';
}
 ?>