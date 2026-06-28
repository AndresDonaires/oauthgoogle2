<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return response()->json([
        'mensaje' => 'API OAuth Google funcionando'
    ]);
});

Route::get('/auth/google', [AuthController::class, 'redirect']);

Route::get('/auth/google/callback', [AuthController::class, 'callback']);

