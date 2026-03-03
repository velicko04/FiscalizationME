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
    public function fiskalizuj($invoiceId)
    {
        \Log::info('=== FISKALIZACIJA START ===', [
            'invoice_id' => $invoiceId
        ]);

        $invoice = Invoice::with([
            'items.product.vatRate',
            'company',
            'buyer',
            'user'
        ])->findOrFail($invoiceId);

        $dto = $this->mapInvoiceToDTO($invoice);

        $uuid = (string) Str::uuid();
        $sendDateTime = now()->format(DATE_ATOM);

        $certPath = config('services.tax.cert_path');
        $password = config('services.tax.cert_password');

        if (!$certPath || !$password) {
            \Log::error('Nedostaje sertifikat ili lozinka');
            return response()->json([
                'status' => 500,
                'body' => 'Certifikat i lozinka su obavezni za fiskalizaciju.'
            ]);
        }

        try {

            [$iic, $iicSignature] = $this->generateIICAndSignature(
                $invoice,
                $certPath,
                $password
            );

            \Log::info('IIC generisan', [
                'iic' => $iic,
                'iic_length' => strlen($iic),
                'iic_signature_length' => strlen($iicSignature),
            ]);

            $dto->iic = $iic;
            $dto->iic_signature = $iicSignature;

            $xml = FiscalXmlBuilder::build($dto, $uuid, $sendDateTime);

            \Log::info('XML prije potpisa', [
                'xml_length' => strlen($xml)
            ]);

            $xml = $this->signXml($xml, $certPath, $password);

            \Log::info('XML spreman za slanje', [
                'xml_length' => strlen($xml)
            ]);

        } catch (\Exception $e) {

            \Log::error('Fiskalizacija greška', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 500,
                'body' => $e->getMessage()
            ]);
        }

        $response = $this->sendToTax($xml);

        \Log::info('=== FISKALIZACIJA END ===', [
            'response_status' => $response['status']
        ]);

        return response()->json($response);
    }

    protected function generateIICAndSignature($invoice, $certPath, $password)
    {
        $certContent = file_get_contents($certPath);

        if (!openssl_pkcs12_read($certContent, $certs, $password)) {
            throw new \Exception("Ne mogu da učitam sertifikat.");
        }

        $privateKey = $certs['pkey'];

        $dataToSign =
            $invoice->company->tax_id_number .
            '|' .
            $invoice->issued_at .
            '|' .
            $invoice->invoice_number .
            '|' .
            number_format($invoice->total_price_to_pay, 2, '.', '');

        // IIC mora biti 32 hex (MD5)
        $iic = md5($dataToSign);

        if (strlen($iic) !== 32) {
            throw new \Exception("IIC nije 32 hex.");
        }

        openssl_sign(
            $iic,
            $signatureBinary,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        $iicSignature = strtoupper(bin2hex($signatureBinary));

        if (strlen($iicSignature) !== 512) {
            throw new \Exception("IICSignature nije 512 hex.");
        }

        return [$iic, $iicSignature];
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

        \Log::info('Odgovor poreske', [
            'http_code' => $httpCode,
            'curl_error' => $err,
            'response_body' => $response
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
        $certContent = file_get_contents($certPath);

        if (!openssl_pkcs12_read($certContent, $certs, $password)) {
            throw new \Exception("Ne mogu da učitam sertifikat.");
        }

        $privateKey = $certs['pkey'];

        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $canonicalXml = $dom->C14N();

        openssl_sign(
            $canonicalXml,
            $signatureValue,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        $signatureB64 = base64_encode($signatureValue);

        $signatureEl = $dom->createElementNS(
            'http://www.w3.org/2000/09/xmldsig#',
            'Signature'
        );

        $sigValueEl = $dom->createElementNS(
            'http://www.w3.org/2000/09/xmldsig#',
            'SignatureValue',
            $signatureB64
        );

        $signatureEl->appendChild($sigValueEl);
        $dom->documentElement->appendChild($signatureEl);

        return $dom->saveXML();
    }

    protected function mapInvoiceToDTO($invoice)
    {
        $dto = new CreateInvoiceRequest();
        $dto->issued_at = $invoice->issued_at;
        $dto->invoice_number = $invoice->invoice_number;
        $dto->orderNumber = $invoice->order_number;
        $dto->total_price_to_pay = $invoice->total_price_to_pay;
        $dto->total_price_without_vat = $invoice->total_price_without_vat;
        $dto->total_vat_amount = $invoice->total_vat_amount;
        return $dto;
    }
}