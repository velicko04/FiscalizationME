<?php

namespace App\DTO;

class CompanyDTO
{
    public string $business_unit_code;
    public string $software_code;
    public string $enu_code;
    public string $address;
    public string $tax_id_number;
    public string $tax_id_type;
    public string $name;
    public string $city;
    public bool $is_issuer_in_vat;
}