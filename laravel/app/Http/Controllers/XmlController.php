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
        $keyPath  = config('services.tax.key_path');
        if ($certPath && $keyPath) {
            $xml = $this->signXml($xml, $certPath, $keyPath);
        }

        // Slanje na testni endpoint
        $response = $this->sendToTax($xml);

        return response()->json([
            'status' => $response['status'],
            'body' => $response['body']
        ]);
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
        // ✅ Validni test kodovi za poresku
        $companyDTO->business_unit_code = 'ab123cd456';
        $companyDTO->software_code = 'sw123ef789';
        $companyDTO->enu_code = 'en456gh123';
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
}