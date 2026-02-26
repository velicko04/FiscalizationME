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

        // Root element sa namespace
        $root = $dom->createElementNS('https://efi.tax.gov.me/fs/schema', 'RegisterInvoiceRequest');
        $root->setAttribute('Id', 'Request');
        $root->setAttribute('Version', '1');
        $dom->appendChild($root);

        // Header
        $header = $dom->createElement('Header');
        $header->setAttribute('SendDateTime', $sendDateTime);
        $header->setAttribute('UUID', $uuid);
        $root->appendChild($header);

        // Invoice
        $invoiceEl = $dom->createElement('Invoice');

        $company = $invoice->company; // CompanyDTO ili model
        $user = $invoice->user;       // UserDTO ili model

        $invoiceEl->setAttribute('BusinUnitCode', $company->business_unit_code);
        $invoiceEl->setAttribute('IssueDateTime', $invoice->issued_at->format('c'));
        $invoiceEl->setAttribute('IIC', $invoice->iic ?? '');
        $invoiceEl->setAttribute('IICSignature', $invoice->iic_signature ?? '');
        $invoiceEl->setAttribute('InvNum', $invoice->invoice_number);
        $invoiceEl->setAttribute('InvOrdNum', $invoice->orderNumber); // DTO property je camelCase
        $invoiceEl->setAttribute('IsIssuerInVAT', $company->is_issuer_in_vat ? 'true' : 'false');
        $invoiceEl->setAttribute('IsReverseCharge', 'false');
        $invoiceEl->setAttribute('IsSimplifiedInv', 'false');
        $invoiceEl->setAttribute('OperatorCode', $user->operator_code);
        $invoiceEl->setAttribute('SoftCode', $company->software_code);
        $invoiceEl->setAttribute('TCRCode', $company->enu_code);
        $invoiceEl->setAttribute('TotPrice', number_format($invoice->total_price_to_pay, 2, '.', ''));
        $invoiceEl->setAttribute('TotPriceWoVAT', number_format($invoice->total_price_without_vat, 2, '.', ''));
        $invoiceEl->setAttribute('TotVATAmt', number_format($invoice->total_vat_amount, 2, '.', ''));
        $invoiceEl->setAttribute('TypeOfInv', strtoupper($invoice->typeOfInvoice->value)); // enum -> string

        $root->appendChild($invoiceEl);

        // PayMethods
        $payMethods = $dom->createElement('PayMethods');
        $payMethod = $dom->createElement('PayMethod');
        $payMethod->setAttribute('Amt', number_format($invoice->total_price_to_pay, 2, '.', ''));
        $payMethod->setAttribute('Type', strtoupper($invoice->paymentMethod->value)); // enum -> string
        $payMethods->appendChild($payMethod);
        $invoiceEl->appendChild($payMethods);

        // Seller
        $seller = $dom->createElement('Seller');
        $seller->setAttribute('Address', $company->address);
        $seller->setAttribute('Country', 'MNE');
        $seller->setAttribute('IDNum', $company->tax_id_number);
        $seller->setAttribute('IDType', $company->tax_id_type);
        $seller->setAttribute('Name', $company->name);
        $seller->setAttribute('Town', $company->city);
        $invoiceEl->appendChild($seller);

        // Buyer (ako postoji)
        if ($invoice->buyer) {
            $buyer = $dom->createElement('Buyer');
            $buyer->setAttribute('Address', $invoice->buyer->address);
            $buyer->setAttribute('Country', 'MNE');
            $buyer->setAttribute('IDNum', $invoice->buyer->taxIdNumber);
            $buyer->setAttribute('IDType', $invoice->buyer->taxIdType->value); // enum -> string
            $buyer->setAttribute('Name', $invoice->buyer->name);
            $buyer->setAttribute('Town', $invoice->buyer->city);
            $invoiceEl->appendChild($buyer);
        }

        // Items
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

        // SameTaxes
        $sameTaxes = $dom->createElement('SameTaxes');
        $sameTax = $dom->createElement('SameTax');
        $sameTax->setAttribute('NumOfItems', count($invoice->items));
        $sameTax->setAttribute('PriceBefVAT', number_format($invoice->total_price_without_vat, 2, '.', ''));
        $sameTax->setAttribute('VATAmt', number_format($invoice->total_vat_amount, 2, '.', ''));
        $sameTax->setAttribute('VATRate', number_format($invoice->items[0]->vatRate ?? 0, 2, '.', ''));
        $sameTaxes->appendChild($sameTax);
        $invoiceEl->appendChild($sameTaxes);

        return $dom->saveXML();
    }
}