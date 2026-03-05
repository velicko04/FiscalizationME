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
Route::get('/fiscal-logs', [InvoiceController::class, 'logs'])->name('invoices.logs');
