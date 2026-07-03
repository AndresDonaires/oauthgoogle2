<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\ValoracionController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\AreaInteresController;

// ── Rutas públicas (sin autenticación) ───────────────────────────────────
Route::get('/auth/google/redirect', [AuthController::class, 'redirect']);
Route::get('/auth/google/callback', [AuthController::class, 'callback']);

// ── Rutas protegidas (requieren Bearer token de Google) ──────────────────
Route::middleware(['auth.google'])->group(function () {

    // Sesión del usuario autenticado
    Route::get('/usuario',      [AuthController::class, 'usuario']);
    Route::put('/usuario/rol',  [AuthController::class, 'actualizarRol']);

    // Perfiles
    Route::get('/perfiles',       [PerfilController::class, 'index']);
    Route::get('/perfiles/{id}',  [PerfilController::class, 'show']);
    Route::get('/perfiles/{id}/valoraciones', [PerfilController::class, 'valoraciones']);
    Route::post('/perfiles',      [PerfilController::class, 'store']);
    Route::put('/perfiles/{id}',  [PerfilController::class, 'update']);

    // Sesiones
    Route::get('/sesiones',              [SesionController::class, 'index']);
    Route::get('/sesiones/{id}',         [SesionController::class, 'show']);
    Route::post('/sesiones',             [SesionController::class, 'store']);
    Route::put('/sesiones/{id}',         [SesionController::class, 'update']);
    Route::put('/sesiones/{id}/completar', [SesionController::class, 'completar']);
    Route::put('/sesiones/{id}/confirmar', [SesionController::class, 'confirmar']);
    Route::put('/sesiones/{id}/cancelar',  [SesionController::class, 'cancelar']);

    // Valoraciones
    Route::get('/valoraciones',      [ValoracionController::class, 'index']);
    Route::get('/valoraciones/{id}', [ValoracionController::class, 'show']);
    Route::post('/valoraciones',     [ValoracionController::class, 'store']);

    // Áreas de interés — lectura disponible para cualquier usuario autenticado
    // (mentor/aprendiz las eligen en su perfil); crear/editar sigue siendo solo admin.
    Route::get('/areas-interes', [AreaInteresController::class, 'index']);

    // ── Solo Administrador (rol = 3) ─────────────────────────────────────
    Route::middleware(['rol:3'])->group(function () {
        Route::get('/usuarios',             [UsuarioController::class, 'index']);
        Route::put('/usuarios/{id}',        [UsuarioController::class, 'update']);
        Route::get('/admin/estadisticas',   [UsuarioController::class, 'estadisticas']);

        Route::post('/areas-interes',       [AreaInteresController::class, 'store']);
        Route::put('/areas-interes/{id}',   [AreaInteresController::class, 'update']);
    });
});
