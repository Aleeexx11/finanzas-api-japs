<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EgresoController;
use App\Http\Controllers\Api\IngresoController;
use App\Http\Controllers\Api\SubcategoriaController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::middleware('throttle:6,1')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('dashboard/resumen', [DashboardController::class, 'resumen'])
        ->name('dashboard.resumen');

    Route::get(
        'categorias/{categoria}/subcategorias',
        [SubcategoriaController::class, 'indexByCategoria'],
    )->whereNumber('categoria')->name('categorias.subcategorias.index');
    Route::post(
        'categorias/{categoria}/subcategorias',
        [SubcategoriaController::class, 'store'],
    )->whereNumber('categoria')->name('categorias.subcategorias.store');

    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('subcategorias', SubcategoriaController::class);
    Route::apiResource('egresos', EgresoController::class);
    Route::apiResource('ingresos', IngresoController::class);
});
