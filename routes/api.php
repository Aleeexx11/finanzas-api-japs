<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EgresoController;
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
    Route::apiResource('egresos', EgresoController::class);
});
