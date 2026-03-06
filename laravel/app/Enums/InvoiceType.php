<?php

namespace App\Enums;

enum InvoiceType: string
{
    case REGULAR = 'REGULAR';
    case CORRECTIVE = 'CORRECTIVE';
}

?>