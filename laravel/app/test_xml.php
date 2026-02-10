<?php

require __DIR__ . '/../vendor/autoload.php';

use App\DTO\CreateInvoiceRequest;
use App\DTO\BuyerDTO;
use App\DTO\InvoiceItemDTO;
use App\Enums\InvoiceType;
use App\Enums\TypeOfInvoice;
use App\Enums\PaymentMethodType;
use App\Enums\TaxIdType;
use App\Services\XmlService; 

$invoice = new CreateInvoiceRequest();
$invoice->invoiceType = InvoiceType::INVOICE;
$invoice->typeOfInvoice = TypeOfInvoice::NONCASH;
$invoice->orderNumber = 123;
$invoice->paymentMethod = PaymentMethodType::ACCOUNT;

$buyer = new BuyerDTO();
$buyer->taxIdType = TaxIdType::TIN;
$buyer->taxIdNumber = '123456789';
$buyer->name = 'Test Company';
$buyer->country = 'ME';
$buyer->city = 'Podgorica';
$buyer->address = 'Test Street 1';
$invoice->buyer = $buyer;

$item = new InvoiceItemDTO();
$item->name = 'Test Product';
$item->unit = 'pcs';
$item->unitPrice = 10.5;
$item->quantity = 2;
$item->unitPriceAfterVat = 12;
$item->totalPriceBeforeVat = 21;
$item->totalPriceAfterVat = 24;
$item->vatRate = 0.15;
$item->vatAmount = 3;

$invoice->items[] = $item;

$xmlString = XmlService::toXml($invoice, 'Invoice');

echo $xmlString;
