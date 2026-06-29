<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\ValoracionController;

// --- Rutas de Autenticación (KAN-16) ---
Route::get('/auth/google/redirect', [AuthController::class, 'redirect']);
Route::get('/auth/google/callback', [AuthController::class, 'callback']);
Route::get('/usuario', [AuthController::class, 'usuario']);

// --- Rutas de Perfiles (KAN-22) ---
Route::get('/perfiles', [PerfilController::class, 'index']);
Route::get('/perfiles/{id}', [PerfilController::class, 'show']);
Route::post('/perfiles', [PerfilController::class, 'store']);
Route::put('/perfiles/{id}', [PerfilController::class, 'update']);

// --- Rutas de Sesiones ---
Route::get('/sesiones', [SesionController::class, 'index']);
Route::get('/sesiones/{id}', [SesionController::class, 'show']);
Route::post('/sesiones', [SesionController::class, 'store']);
Route::put('/sesiones/{id}', [SesionController::class, 'update']);
Route::put('/sesiones/{id}/cancelar', [SesionController::class, 'cancelar']);

// --- Rutas de Valoraciones ---
Route::get('/valoraciones', [ValoracionController::class, 'index']);
Route::get('/valoraciones/{id}', [ValoracionController::class, 'show']);
Route::post('/valoraciones', [ValoracionController::class, 'store']);