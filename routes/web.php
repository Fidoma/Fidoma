<?php

use App\Features\Authentication\Http\Controllers\LocaleController;
use App\Features\Invoices\Http\Controllers\InvoiceDocumentController;
use App\Features\Invoices\Http\Controllers\InvoiceLayoutPreviewController;
use App\Features\TradeFairs\Http\Controllers\TradeFairController;
use App\Features\Webmail\Http\Controllers\RoundcubeController;
use Illuminate\Support\Facades\Route;

$welcomeRoutes = app_path('Features/Welcome/routes/web.php');

if (is_file($welcomeRoutes)) {
    require $welcomeRoutes;
} else {
    Route::redirect('/', '/admin/login');
}

Route::middleware('auth')->post('admin/locale/{locale}', LocaleController::class)
    ->whereIn('locale', ['de', 'en'])
    ->name('admin.locale.update');

Route::middleware('auth')->prefix('admin/invoices/{invoice}')->group(function (): void {
    Route::get('preview', [InvoiceDocumentController::class, 'preview'])
        ->name('invoices.preview');
    Route::get('pdf', [InvoiceDocumentController::class, 'pdf'])
        ->name('invoices.pdf');
    Route::get('pdf/download', [InvoiceDocumentController::class, 'downloadPdf'])
        ->name('invoices.pdf.download');
    Route::get('xrechnung', [InvoiceDocumentController::class, 'xrechnung'])
        ->name('invoices.xrechnung');
});

Route::middleware('auth')->get('admin/pdf-layouts/pdf-preview', InvoiceLayoutPreviewController::class)
    ->name('pdf-layouts.pdf-preview');

Route::middleware('auth')->prefix('admin/trade-fairs')->name('trade-fairs.')->group(function (): void {
    Route::get('upcoming', [TradeFairController::class, 'upcoming'])->name('upcoming');
    Route::get('range', [TradeFairController::class, 'range'])->name('range');
});

Route::middleware('auth')->any('admin/webmail/client/{path?}', RoundcubeController::class)
    ->where('path', '.*')
    ->name('webmail.client');
