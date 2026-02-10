<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case ACCOUNT = 'ACCOUNT';
    case BUSINESSCARD = 'BUSINESSCARD';
}

?>