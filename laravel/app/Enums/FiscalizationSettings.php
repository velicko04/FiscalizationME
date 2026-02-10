<?php

namespace App\Models\Fiscalization;

use App\Enums\FiscalizationEnvironment;
use App\Enums\TaxIdType;

class FiscalizationSettings
{
    public FiscalizationEnvironment $environment;
    public TaxIdType $issuerIdType;
    public string $issuerIdNumber;
    public string $issuerName;
    public string $issuerAddress;
    public string $issuerCity;
    public string $issuerCountry;
    public string $issuerBusinessUnitCode;
    public string $issuerSoftwareCode;
    public string $issuerOperatorCode;
    public string $issuerEnuCode;

    public function __construct(array $data)
    {
        $this->environment = $data['environment'];
        $this->issuerIdType = $data['issuerIdType'];
        $this->issuerIdNumber = $data['issuerIdNumber'];
        $this->issuerName = $data['issuerName'];
        $this->issuerAddress = $data['issuerAddress'];
        $this->issuerCity = $data['issuerCity'];
        $this->issuerCountry = $data['issuerCountry'];
        $this->issuerBusinessUnitCode = $data['issuerBusinessUnitCode'];
        $this->issuerSoftwareCode = $data['issuerSoftwareCode'];
        $this->issuerOperatorCode = $data['issuerOperatorCode'];
        $this->issuerEnuCode = $data['issuerEnuCode'];
    }
}
