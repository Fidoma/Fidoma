<?php

use App\Features\Welcome\Http\Controllers\InstallScriptController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome::welcome');
Route::view('/preise', 'welcome::pricing')->name('pricing');
Route::get('/install.sh', InstallScriptController::class)->name('welcome.install');
