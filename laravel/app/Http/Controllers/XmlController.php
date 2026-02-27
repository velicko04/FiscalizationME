<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\DTO\CreateInvoiceRequest;
use App\DTO\BuyerDTO;
use App\DTO\CompanyDTO;
use App\DTO\UserDTO;
use App\Enums\TypeOfInvoice;
use App\Enums\PaymentMethodType;
use App\Services\FiscalXmlBuilder;
use Illuminate\Support\Str;

class XmlController extends Controller
{
    public function generate($invoiceId)
    {
        $invoice = Invoice::with([
            'contract',
            'items.product',
            'items.vatRate',
            'company',
            'buyer',
            'user'
        ])->findOrFail($invoiceId);

        $dto = $this->mapInvoiceToDTO($invoice);

        $uuid = (string) Str::uuid();
        $sendDateTime = now()->format(DATE_ATOM);

        $xml = FiscalXmlBuilder::build($dto, $uuid, $sendDateTime);

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    public function fiskalizuj($invoiceId)
    {
        $invoice = Invoice::with([
            'items.product.vatRate',
            'company',
            'buyer',
            'user'
        ])->findOrFail($invoiceId);

        $dto = $this->mapInvoiceToDTO($invoice);

        $uuid = (string) Str::uuid();
        $sendDateTime = now()->format(DATE_ATOM);

        $xml = FiscalXmlBuilder::build($dto, $uuid, $sendDateTime);

        // Opcionalno: digitalni potpis
        $certPath = config('services.tax.cert_path');
        $password = config('services.tax.cert_password'); // lozinka iz .env
        try {
            if ($certPath && $password) {
                $xml = $this->signXml($xml, $certPath, $password);
            }
        } catch (\Exception $e) {
            \Log::error('Greška prilikom potpisa XML:', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'body' => "Greška pri potpisu: " . $e->getMessage()
            ]);
        }

        // Slanje na testni endpoint
        $response = $this->sendToTax($xml);

        return response()->json($response);
    }

    protected function mapInvoiceToDTO($invoice)
    {
        $dto = new CreateInvoiceRequest();
        $dto->issued_at = $invoice->issued_at;
        $dto->iic = $invoice->iic;
        $dto->iic_signature = $invoice->iic_signature;
        $dto->invoice_number = $invoice->invoice_number;
        $dto->orderNumber = $invoice->order_number;

        $dto->typeOfInvoice = is_string($invoice->type_of_invoice) 
            ? TypeOfInvoice::from($invoice->type_of_invoice) 
            : $invoice->type_of_invoice;

        $dto->paymentMethod = is_string($invoice->payment_method_type)
            ? PaymentMethodType::from($invoice->payment_method_type)
            : $invoice->payment_method_type;

        $dto->total_price_to_pay = $invoice->total_price_to_pay;
        $dto->total_price_without_vat = $invoice->total_price_without_vat;
        $dto->total_vat_amount = $invoice->total_vat_amount;

        // Buyer
        if ($invoice->buyer) {
            $buyerDTO = new BuyerDTO();
            $buyerDTO->name = $invoice->buyer->name;
            $buyerDTO->address = $invoice->buyer->address;
            $buyerDTO->city = $invoice->buyer->city;
            $buyerDTO->taxIdNumber = $invoice->buyer->tax_id_number;
            $buyerDTO->taxIdType = $invoice->buyer->tax_id_type;
            $dto->buyer = $buyerDTO;
        }

        // Company
        $companyDTO = new CompanyDTO();
        $companyDTO->name = $invoice->company->name;
        $companyDTO->address = $invoice->company->address;
        $companyDTO->city = $invoice->company->city;
        $companyDTO->business_unit_code = $invoice->company->business_unit_code;
        $companyDTO->software_code = $invoice->company->software_code;
        $companyDTO->enu_code = $invoice->company->enu_code;
        $companyDTO->tax_id_number = $invoice->company->tax_id_number;
        $companyDTO->tax_id_type = $invoice->company->tax_id_type;
        $companyDTO->is_issuer_in_vat = $invoice->company->is_issuer_in_vat;
        $dto->company = $companyDTO;

        // User
        $userDTO = new UserDTO();
        $userDTO->operator_code = $invoice->user->operator_code;
        $dto->user = $userDTO;

        // Stavke
        $dto->items = $invoice->items->map(function ($item) {
            $i = new \stdClass();
            $i->code = $item->product->code;
            $i->name = $item->product->name;
            $i->quantity = $item->quantity;
            $i->unit = $item->product->unit;
            $i->unitPrice = $item->unit_price;
            $i->unitPriceAfterVat = $item->unit_price * (1 + ($item->vatRate->percentage / 100));
            $i->totalPriceBeforeVat = $item->quantity * $item->unit_price;
            $i->totalPriceAfterVat = $i->totalPriceBeforeVat * (1 + ($item->vatRate->percentage / 100));
            $i->vatRate = $item->vatRate->percentage;
            $i->vatAmount = $i->totalPriceAfterVat - $i->totalPriceBeforeVat;
            return $i;
        })->toArray();

        return $dto;
    }

    protected function sendToTax(string $xml)
    {
        $endpoint = 'https://efitest.tax.gov.me/fs-v1/FiscalizationService';

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml; charset=utf-8',
            'Content-Length: ' . strlen($xml),
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log za debug
        \Log::info('Fiskalizacija cURL debug', [
            'http_code' => $httpCode,
            'error' => $err,
            'response' => $response,
            'xml_length' => strlen($xml),
        ]);

        if ($err) {
            return [
                'status' => 500,
                'body' => "cURL greška: $err"
            ];
        }

        return [
            'status' => $httpCode,
            'body' => $response
        ];
    }

    protected function signXml(string $xml, string $certPath, string $password): string
{
    if (!file_exists($certPath)) {
        throw new \Exception("Ne mogu da pronađem sertifikat na: $certPath");
    }

    $certContent = file_get_contents($certPath);
    if (!openssl_pkcs12_read($certContent, $certs, $password)) {
        throw new \Exception("Ne mogu da učitam sertifikat. Proveri lozinku i putanju.");
    }

    $privateKey = $certs['pkey'] ?? null;
    $publicCert = $certs['cert'] ?? null;

    if (!$privateKey || !$publicCert) {
        throw new \Exception("Sertifikat ne sadrži privatni ključ ili javni certifikat.");
    }

    // Kreiramo DOMDocument iz XML stringa
    $dom = new \DOMDocument();
    $dom->loadXML($xml);

    // Ako FiscalXmlBuilder već ne ubacuje <Signature>, možemo napraviti placeholder
    $signatureEl = $dom->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')->item(0);
    if (!$signatureEl) {
        $signatureEl = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
        $dom->documentElement->appendChild($signatureEl);
    }

    // Canonical form XML-a
    $canonicalXml = $dom->C14N();

    // Potpisivanje
    $privateKeyId = openssl_pkey_get_private($privateKey);
    openssl_sign($canonicalXml, $signatureValue, $privateKeyId, OPENSSL_ALGO_SHA256);
    $signatureB64 = base64_encode($signatureValue);

    // Ubacujemo potpis u Signature element
    while ($signatureEl->firstChild) {
        $signatureEl->removeChild($signatureEl->firstChild);
    }
    $sigValueEl = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'SignatureValue', $signatureB64);
    $signatureEl->appendChild($sigValueEl);

    return $dom->saveXML();
}
}