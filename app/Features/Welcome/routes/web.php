<?php

use App\Features\Welcome\Http\Controllers\InstallScriptController;
use App\Features\Welcome\Http\Controllers\SendContactRequestController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome::welcome');
Route::view('/preise', 'welcome::pricing')->name('pricing');
Route::post('/kontakt', SendContactRequestController::class)
    ->middleware('throttle:5,1')
    ->name('welcome.contact.send');
Route::get('/install.sh', InstallScriptController::class)->name('welcome.install');
