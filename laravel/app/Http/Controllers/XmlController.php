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
use App\Models\FiscalLog;

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
    $password = config('services.tax.cert_password') ?: config('services.tax.key_path');

    $requestXml = null;
    $responseXml = null;
    $status = 'ERROR';
    $errorMessage = null;

    if (!$certPath || !$password) {

        $errorMessage = 'Certifikat i lozinka su obavezni za fiskalizaciju.';

        FiscalLog::create([
            'invoice_id' => $invoiceId,
            'request_xml' => null,
            'response_xml' => null,
            'status' => 'ERROR',
            'error_message' => $errorMessage,
        ]);

        return response()->json([
            'status' => 500,
            'body' => $errorMessage
        ]);
    }

    try {

        [$iic, $iicSignature] = $this->generateIICAndSignature(
            $invoice,
            $certPath,
            $password
        );

        $dto->iic = $iic;
        $dto->iic_signature = $iicSignature;

        $xml = FiscalXmlBuilder::build($dto, $uuid, $sendDateTime);
        $requestXml = $xml;

        $xml = $this->signXml($xml, $certPath, $password);

    } catch (\Exception $e) {

        $errorMessage = $e->getMessage();

        FiscalLog::create([
            'invoice_id' => $invoiceId,
            'request_xml' => $requestXml,
            'response_xml' => null,
            'status' => 'ERROR',
            'error_message' => $errorMessage,
        ]);

        return response()->json([
            'status' => 500,
            'body' => $errorMessage
        ]);
    }

    $response = $this->sendToTax($xml);

    $responseXml = $response['body'] ?? null;
    $status = $response['status'] == 200 ? 'SUCCESS' : 'ERROR';

    FiscalLog::create([
        'invoice_id' => $invoiceId,
        'request_xml' => $requestXml,
        'response_xml' => $responseXml,
        'status' => $status,
        'error_message' => $status === 'ERROR' ? $responseXml : null,
    ]);

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

        $invoiceTypeRaw = strtoupper((string) ($invoice->getRawOriginal('invoice_type') ?? 'INVOICE'));
        $dto->invoiceType = match ($invoiceTypeRaw) {
            'CORRECTIVE' => \App\Enums\InvoiceType::CORRECTIVE,
            default => \App\Enums\InvoiceType::REGULAR,
        };

        $typeOfInvoiceRaw = strtoupper((string) ($invoice->getRawOriginal('type_of_invoice') ?? 'NONCASH'));
        $dto->typeOfInvoice = match ($typeOfInvoiceRaw) {
            'CASH' => TypeOfInvoice::CASH,
            default => TypeOfInvoice::NONCASH,
        };

        $paymentRaw = strtoupper((string) ($invoice->getRawOriginal('payment_method_type') ?? 'CASH'));
        $dto->paymentMethod = match ($paymentRaw) {
            'CARD' => PaymentMethodType::CARD,
            default => PaymentMethodType::CASH,
        };

        $dto->issued_at = $invoice->issued_at;
        $dto->orderNumber = (int) ($invoice->order_number ?? 1);

        $dto->company = $invoice->company;
        $year = $invoice->issued_at ? $invoice->issued_at->format('Y') : date('Y');
        $leftToken = strtolower((string) ($invoice->company->business_unit_code ?? ''));
        $rightToken = strtolower((string) ($invoice->company->enu_code ?? ''));
        $rawInvNum = (string) ($invoice->invoice_number ?? '');

        if (preg_match('/^[a-z]{2}[0-9]{3}[a-z]{2}[0-9]{3}\/[1-9][0-9]{0,14}\/[0-9]{4}\/[a-z]{2}[0-9]{3}[a-z]{2}[0-9]{3}$/', strtolower($rawInvNum))) {
            $dto->invoice_number = strtolower($rawInvNum);
        } elseif (preg_match('/^[a-z]{2}[0-9]{3}[a-z]{2}[0-9]{3}$/', $leftToken) && preg_match('/^[a-z]{2}[0-9]{3}[a-z]{2}[0-9]{3}$/', $rightToken)) {
            $dto->invoice_number = sprintf('%s/%d/%s/%s', $leftToken, max(1, $dto->orderNumber), $year, $rightToken);
        } else {
            $dto->invoice_number = $rawInvNum;
        }

        $dto->user = $invoice->user;

        if ($invoice->buyer) {
            $buyer = new BuyerDTO();
            $buyer->taxIdType = $invoice->buyer->tax_id_type;
            $buyer->taxIdNumber = $invoice->buyer->tax_id_number;
            $buyer->name = $invoice->buyer->name;
            $buyer->country = $invoice->buyer->country ?? 'MNE';
            $buyer->city = $invoice->buyer->city ?? '';
            $buyer->address = $invoice->buyer->address ?? '';
            $dto->buyer = $buyer;
        }

        foreach ($invoice->items as $item) {
            $row = new \App\DTO\InvoiceItemDTO();
            $row->code = $item->product->code ?? null;
            $row->name = $item->product->name ?? 'Stavka';
            $row->unit = $item->product->unit ?? 'kom';

            $qty = (float) ($item->quantity ?? 0);
            $unitPrice = (float) ($item->unit_price ?? 0);
            $vatRate = (float) ($item->vatRate->percentage ?? $item->product->vatRate->percentage ?? 0);

            $row->quantity = $qty;
            $row->unitPrice = $unitPrice;
            $row->totalPriceBeforeVat = round($qty * $unitPrice, 2);
            $row->vatRate = $vatRate;
            $row->vatAmount = round($row->totalPriceBeforeVat * ($vatRate / 100), 2);
            $row->totalPriceAfterVat = round($row->totalPriceBeforeVat + $row->vatAmount, 2);
            $row->unitPriceAfterVat = $qty > 0
                ? round($row->totalPriceAfterVat / $qty, 2)
                : round($unitPrice * (1 + $vatRate / 100), 2);

            $dto->items[] = $row;
        }

        $dto->total_price_to_pay = (float) $invoice->total_price_to_pay;
        $dto->total_price_without_vat = (float) $invoice->total_price_without_vat;
        $dto->total_vat_amount = (float) $invoice->total_vat_amount;

        return $dto;
    }
}