<?php

namespace App\Enums;

enum TaxIdType: string
{
    case TIN = 'TIN';
    case ID = 'ID';
    case PASS = 'PASS';
    case VAT = 'VAT';
    case TAX = 'TAX';
    case SOC = 'SOC';
}
 ?>