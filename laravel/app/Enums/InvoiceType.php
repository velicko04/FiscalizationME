<?php

namespace App\Enums;

enum InvoiceType: string
{
    case CORRECTIVE = 'CORRECTIVE';
    case INVOICE = 'INVOICE';
    case SUMMARY = 'SUMMARY'; 
    case PERIODICAL = 'PERIODICAL';
    case ADVANCE = 'ADVANCE';
    case  CREDIT_NOTE = 'CREDIT_NOTE';
}

?>