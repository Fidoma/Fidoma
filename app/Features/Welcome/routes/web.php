<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome::welcome');
Route::view('/preise', 'welcome::pricing')->name('pricing');
