<?php

namespace App\DTO;

use App\Enums\TaxIdType;

class BuyerDTO
{
    public TaxIdType $taxIdType;
    public string $taxIdNumber;
    public string $name;
    public string $country;
    public string $city;
    public string $address;
}

?>