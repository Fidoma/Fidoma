<?php

use App\Features\Invoices\Http\Controllers\InvoiceDocumentController;
use App\Features\Invoices\Http\Controllers\InvoiceLayoutPreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->prefix('admin/invoices/{invoice}')->group(function (): void {
    Route::get('preview', [InvoiceDocumentController::class, 'preview'])
        ->name('invoices.preview');
    Route::get('pdf', [InvoiceDocumentController::class, 'pdf'])
        ->name('invoices.pdf');
    Route::get('xrechnung', [InvoiceDocumentController::class, 'xrechnung'])
        ->name('invoices.xrechnung');
});

Route::middleware('auth')->get('admin/pdf-layouts/pdf-preview', InvoiceLayoutPreviewController::class)
    ->name('pdf-layouts.pdf-preview');
