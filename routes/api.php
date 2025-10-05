<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\BarberController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rutas para servicios
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::post('/services', [ServiceController::class, 'store']);

    //Rutas que aun no se sabe si usaremos

    //Route::put('/services/{id}', [ServiceController::class, 'update']);
    //Route::delete('/services/{id}', [ServiceController::class, 'destroy']);


     Route::get('barbers/schedule', [BarberController::class, 'schedule']); // <-- RUTA DE DISPONIBILIDAD
});