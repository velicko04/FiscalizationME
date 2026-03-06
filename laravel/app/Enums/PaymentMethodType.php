<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case CARD = 'CARD';
    case BANKNOTE = 'BANKNOTE';
    case ACCOUNT = 'ACCOUNT';
    case COMPENSATION = 'COMPENSATION';
    case VOUCHER = 'VOUCHER';
    case OTHER = 'OTHER';
}

?>