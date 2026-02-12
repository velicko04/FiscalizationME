<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case CASH = 'CASH';
    case CARD = 'CARD';
}

?>