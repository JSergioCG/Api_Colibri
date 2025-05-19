<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmailController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/send-test-email', [EmailController::class, 'sendTestEmail']);
