<?php

namespace App\Enums;

enum TypeOfInvoice: string
{
    case CASH = 'CASH';
    case NONCASH = 'NONCASH';
}

?>