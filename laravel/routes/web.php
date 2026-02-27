<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Services\FiscalXmlBuilder;
use App\DTO\CreateInvoiceRequest;
use App\DTO\BuyerDTO;
use App\DTO\InvoiceItemDTO;
use App\DTO\CompanyDTO;
use App\DTO\UserDTO;
use App\Enums\InvoiceType;
use App\Enums\TypeOfInvoice;
use App\Enums\PaymentMethodType;
use App\Enums\TaxIdType;
use App\Http\Controllers\XmlController;


Route::get('/invoice/{id}/xml', [XmlController::class, 'generate'])->name('invoice.xml');
Route::post('/invoice/{id}/fiskalizuj', [XmlController::class, 'fiskalizuj'])->name('invoice.fiskalizuj');
Route::get('/test-invoice-xml', function () {

    $invoiceDTO = new CreateInvoiceRequest();
    $invoiceDTO->invoiceType = InvoiceType::INVOICE;
    $invoiceDTO->typeOfInvoice = TypeOfInvoice::NONCASH;
    $invoiceDTO->orderNumber = 1;
    $invoiceDTO->paymentMethod = PaymentMethodType::CASH;

    // Buyer
    $buyer = new BuyerDTO();
    $buyer->taxIdType = TaxIdType::TIN;
    $buyer->taxIdNumber = '87654321';
    $buyer->name = 'Telekom CG';
    $buyer->country = 'ME';
    $buyer->city = 'Podgorica';
    $buyer->address = 'Moskovska 29';
    $invoiceDTO->buyer = $buyer;

    // Company
    $company = new CompanyDTO();
    $company->business_unit_code = 'BU001';
    $company->software_code = 'SW001';
    $company->enu_code = 'ENU001';
    $company->address = 'Bulevar Svetog Petra 12';
    $company->tax_id_number = '12345678';
    $company->tax_id_type = 'PIB';
    $company->name = 'Tech Solutions DOO';
    $company->city = 'Podgorica';
    $company->is_issuer_in_vat = true;
    $invoiceDTO->company = $company;

    // User
    $user = new UserDTO();
    $user->operator_code = 'OP001';
    $invoiceDTO->user = $user;

    // Item
    $item = new InvoiceItemDTO();
    $item->name = 'Magenta Paket';
    $item->unit = 'kom';
    $item->unitPrice = 50.00;
    $item->quantity = 1;
    $item->unitPriceAfterVat = 60.50;
    $item->totalPriceBeforeVat = 50.00;
    $item->totalPriceAfterVat = 60.50;
    $item->vatRate = 21.00;
    $item->vatAmount = 10.50;
    $invoiceDTO->items[] = $item;

    // Dodaj polja koja FiscalXmlBuilder koristi
    $invoiceDTO->total_price_to_pay = 60.50;
    $invoiceDTO->total_price_without_vat = 50.00;
    $invoiceDTO->total_vat_amount = 10.50;
    $invoiceDTO->invoice_number = 'INV-001';
    $invoiceDTO->issued_at = new \DateTime();
    $invoiceDTO->iic = 'IIC123456789';
    $invoiceDTO->iic_signature = 'SIGNATURE_HASH';

    $uuid = \Str::uuid()->toString();
    $sendDateTime = now()->format('c');

    $xmlString = FiscalXmlBuilder::build($invoiceDTO, $uuid, $sendDateTime);

    return response($xmlString, 200)->header('Content-Type', 'application/xml');
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
Route::get('/contracts/{id}/invoices', [ContractController::class, 'invoices'])->name('contracts.invoices');
Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
Route::get('/contracts/{id}/edit', [ContractController::class, 'edit'])->name('contracts.edit');
Route::put('/contracts/{id}', [ContractController::class, 'update'])->name('contracts.update');
Route::post('/products/ajax-store', [ProductController::class, 'ajaxStore'])->name('products.ajaxStore');

