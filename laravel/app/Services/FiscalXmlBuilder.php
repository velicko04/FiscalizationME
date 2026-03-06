<?php

namespace App\Services;

use App\DTO\CreateInvoiceRequest;
use DOMDocument;

class FiscalXmlBuilder
{
    public static function build(CreateInvoiceRequest $invoice, string $uuid, string $sendDateTime): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        /*
        |--------------------------------------------------------------------------
        | SOAP ENVELOPE
        |--------------------------------------------------------------------------
        */
        $envelope = $dom->createElementNS(
            'http://schemas.xmlsoap.org/soap/envelope/',
            'SOAP-ENV:Envelope'
        );
        $dom->appendChild($envelope);

        $soapHeader = $dom->createElement('SOAP-ENV:Header');
        $envelope->appendChild($soapHeader);

        $soapBody = $dom->createElement('SOAP-ENV:Body');
        $envelope->appendChild($soapBody);

        /*
        |--------------------------------------------------------------------------
        | RegisterInvoiceRequest
        |--------------------------------------------------------------------------
        */
        $root = $dom->createElementNS(
            'https://efi.tax.gov.me/fs/schema',
            'RegisterInvoiceRequest'
        );

        $root->setAttribute('xmlns:ns2', 'http://www.w3.org/2000/09/xmldsig#');
        $root->setAttribute('Id', 'Request');
        $root->setAttribute('Version', '1');

        $soapBody->appendChild($root);

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */
        $header = $dom->createElement('Header');
        $header->setAttribute('SendDateTime', $sendDateTime);
        $header->setAttribute('UUID', $uuid);
        $root->appendChild($header);

        /*
        |--------------------------------------------------------------------------
        | INVOICE
        |--------------------------------------------------------------------------
        */
        $company = $invoice->company;
        $user = $invoice->user;

        $invoiceEl = $dom->createElement('Invoice');

        $invoiceEl->setAttribute('BusinUnitCode', $company->business_unit_code);
        $invoiceEl->setAttribute('IssueDateTime', $invoice->issued_at->format('c'));
        $invoiceEl->setAttribute('IIC', $invoice->iic ?? '');
        $invoiceEl->setAttribute('IICSignature', $invoice->iic_signature ?? '');
        $invoiceEl->setAttribute('InvNum', $invoice->invoice_number);
        $invoiceEl->setAttribute('InvOrdNum', $invoice->orderNumber);
        $invoiceEl->setAttribute('IsIssuerInVAT', $company->is_issuer_in_vat ? 'true' : 'false');
        $invoiceEl->setAttribute('IsReverseCharge', 'false');
        $invoiceEl->setAttribute('OperatorCode', $user->operator_code);
        $invoiceEl->setAttribute('SoftCode', $company->software_code);
        $invoiceEl->setAttribute('TCRCode', $company->enu_code);
        $invoiceEl->setAttribute('TotPrice', number_format($invoice->total_price_to_pay, 2, '.', ''));
        $invoiceEl->setAttribute('TotPriceWoVAT', number_format($invoice->total_price_without_vat, 2, '.', ''));
        $invoiceEl->setAttribute('TotVATAmt', number_format($invoice->total_vat_amount, 2, '.', ''));
        $invoiceEl->setAttribute('TypeOfInv', strtoupper($invoice->invoiceType));
        $invoiceEl->setAttribute('InvType', strtoupper($invoice->typeOfInvoice));

        $root->appendChild($invoiceEl);

        /*
        |--------------------------------------------------------------------------
        | PAYMENT METHODS
        |--------------------------------------------------------------------------
        */
        $payMethods = $dom->createElement('PayMethods');
        $payMethod = $dom->createElement('PayMethod');
        $payMethod->setAttribute('Amt', number_format($invoice->total_price_to_pay, 2, '.', ''));
        $payMethod->setAttribute('Type', strtoupper($invoice->paymentMethod->value));
        $payMethods->appendChild($payMethod);
        $invoiceEl->appendChild($payMethods);

        /*
        |--------------------------------------------------------------------------
        | SELLER
        |--------------------------------------------------------------------------
        */
        $seller = $dom->createElement('Seller');
        $seller->setAttribute('Address', $company->address);
        $seller->setAttribute('Country', 'MNE');
        $seller->setAttribute('IDNum', $company->tax_id_number);
        $seller->setAttribute('IDType', $company->tax_id_type->value);
        $seller->setAttribute('Name', $company->name);
        $seller->setAttribute('Town', $company->city);

        $invoiceEl->appendChild($seller);

        /*
        |--------------------------------------------------------------------------
        | BUYER 
        |--------------------------------------------------------------------------
        */
        if ($invoice->buyer) {
            $buyerDTO = $invoice->buyer;

            $buyer = $dom->createElement('Buyer');
            $buyer->setAttribute('Address', $buyerDTO->address);
            $buyer->setAttribute('Country', 'MNE');
            $buyer->setAttribute('IDNum', $buyerDTO->taxIdNumber);
            $buyer->setAttribute('IDType', $buyerDTO->taxIdType->value);
            $buyer->setAttribute('Name', $buyerDTO->name);
            $buyer->setAttribute('Town', $buyerDTO->city);

            $invoiceEl->appendChild($buyer);
        }

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */
        $itemsEl = $dom->createElement('Items');

        foreach ($invoice->items as $item) {
            $iEl = $dom->createElement('I');

            $iEl->setAttribute('C', $item->code ?? '');
            $iEl->setAttribute('N', $item->name);
            $iEl->setAttribute('Q', number_format($item->quantity, 2, '.', ''));
            $iEl->setAttribute('U', $item->unit);
            $iEl->setAttribute('UPB', number_format($item->unitPrice, 2, '.', ''));
            $iEl->setAttribute('UPA', number_format($item->unitPriceAfterVat, 2, '.', ''));
            $iEl->setAttribute('PB', number_format($item->totalPriceBeforeVat, 2, '.', ''));
            $iEl->setAttribute('PA', number_format($item->totalPriceAfterVat, 2, '.', ''));
            $iEl->setAttribute('VR', number_format($item->vatRate, 2, '.', ''));
            $iEl->setAttribute('VA', number_format($item->vatAmount, 2, '.', ''));
            $iEl->setAttribute('R', '0');
            $iEl->setAttribute('RR', 'false');

            $itemsEl->appendChild($iEl);
        }

        $invoiceEl->appendChild($itemsEl);

        /*
        |--------------------------------------------------------------------------
        | SAME TAXES (grupisanje po stopi)
        |--------------------------------------------------------------------------
        */
        $taxGroups = [];

        foreach ($invoice->items as $item) {
            $rate = $item->vatRate;

            if (!isset($taxGroups[$rate])) {
                $taxGroups[$rate] = [
                    'num' => 0,
                    'base' => 0,
                    'vat' => 0
                ];
            }

            $taxGroups[$rate]['num']++;
            $taxGroups[$rate]['base'] += $item->totalPriceBeforeVat;
            $taxGroups[$rate]['vat'] += $item->vatAmount;
        }

        $sameTaxes = $dom->createElement('SameTaxes');

        foreach ($taxGroups as $rate => $data) {
            $sameTax = $dom->createElement('SameTax');

            $sameTax->setAttribute('NumOfItems', $data['num']);
            $sameTax->setAttribute('PriceBefVAT', number_format($data['base'], 2, '.', ''));
            $sameTax->setAttribute('VATAmt', number_format($data['vat'], 2, '.', ''));
            $sameTax->setAttribute('VATRate', number_format($rate, 2, '.', ''));

            $sameTaxes->appendChild($sameTax);
        }

        $invoiceEl->appendChild($sameTaxes);

        /*
        |--------------------------------------------------------------------------
        | Signature placeholder 
        |--------------------------------------------------------------------------
        */
        $signature = $dom->createElementNS(
            'http://www.w3.org/2000/09/xmldsig#',
            'ns2:Signature'
        );
        $root->appendChild($signature);

        return $dom->saveXML();
    }
}