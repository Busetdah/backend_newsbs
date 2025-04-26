<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamStitchController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/ssd', [StreamStitchController::class, 'sendData']);
