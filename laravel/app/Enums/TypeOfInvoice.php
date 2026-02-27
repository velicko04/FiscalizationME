<?php

namespace App\Enums;

enum TypeOfInvoice: string
{
    case NONCASH = 'NONCASH';
    case SALE = 'SALE';
}

?>