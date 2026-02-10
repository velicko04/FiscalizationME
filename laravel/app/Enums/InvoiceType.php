<?php

namespace App\Enums;

enum InvoiceType: string
{
    case INVOICE = 'INVOICE';
    case CORRECTIVE = 'CORRECTIVE';
}

?>