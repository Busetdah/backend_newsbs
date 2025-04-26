<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StreamStitchController;

Route::get('/', function () {
    return view('welcome');
});
Route::middleware(['cors'])->group(function () {

});
