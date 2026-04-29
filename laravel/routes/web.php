<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\XmlController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\StornoController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ChatController;


// Auth rute
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Zaštićene rute
Route::middleware(['auth'])->group(function () {

    Route::get('/', function () {
        return redirect('/contracts');
    });

    Route::get('/contracts', [ContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create', [ContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts', [ContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{id}/edit', [ContractController::class, 'edit'])->name('contracts.edit');
    Route::put('/contracts/{id}', [ContractController::class, 'update'])->name('contracts.update');
    Route::get('/contracts/{id}/invoices', [ContractController::class, 'invoices'])->name('contracts.invoices');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoice/{id}/pdf', [InvoiceController::class, 'pdf'])->name('invoice.pdf');

    Route::get('/fiscal-logs', [InvoiceController::class, 'logs'])->name('invoices.logs');

    Route::get('/invoice/{id}/xml', [XmlController::class, 'generate'])->name('invoice.xml');
    Route::post('/invoice/{id}/fiskalizuj', [XmlController::class, 'fiskalizuj'])->name('invoice.fiskalizuj');

    Route::post('/products/ajax-store', [ProductController::class, 'ajaxStore'])->name('products.ajaxStore');
    Route::post('/buyers/ajax-store', [BuyerController::class, 'ajaxStore'])->name('buyers.ajaxStore');

    Route::get('/companies', [CompanyController::class, 'index'])->name('companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('companies.store');
    Route::get('/companies/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
    Route::put('/companies/{id}', [CompanyController::class, 'update'])->name('companies.update');
    Route::post('/companies/{companyId}/operators', [OperatorController::class, 'store'])->name('operators.store');
    Route::put('/operators/{id}', [OperatorController::class, 'update'])->name('operators.update');
    Route::delete('/operators/{id}', [OperatorController::class, 'destroy'])->name('operators.destroy');

    Route::get('/invoice/{id}/qrcode', [XmlController::class, 'qrCode'])->name('invoice.qrcode');
    
    Route::post('/invoice/{id}/storno', [StornoController::class, 'store'])->name('invoice.storno');

    Route::get('/invoice/{id}/logs', [InvoiceController::class, 'invoiceLogs'])->name('invoice.logs');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
    Route::get('/search', [SettingsController::class, 'search'])->name('search');

    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/stream', [ChatController::class, 'stream'])->name('chat.stream');
});