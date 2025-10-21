<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\BarberController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AdminController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

    // Rutas para servicios
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{id}', [ServiceController::class, 'show']);
    Route::post('/services', [ServiceController::class, 'store']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);


    //Rutas que aun no se sabe si usaremos

    //Route::put('/services/{id}', [ServiceController::class, 'update']);
    //Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

     Route::get('barbers/schedule', [BarberController::class, 'schedule']); // <-- RUTA DE DISPONIBILIDAD
     Route::post('appointments', [AppointmentController::class, 'store']); // <-- NUEVA RUTA DE CREACIÓN

    Route::get('appointments/mine', [AppointmentController::class, 'index']);      // Para Clientes
    Route::get('barber/appointments', [AppointmentController::class, 'index']);   // Para Barberos

     //  GESTIÓN DE PAGOS
    Route::get('barbers/{barber_id}/payment-methods', [PaymentController::class, 'getBarberPaymentMethods']); 
    Route::post('appointments/{appointment_id}/payment', [PaymentController::class, 'uploadPaymentProof']);   

    Route::post('appointments/{appointment_id}/confirm-payment', [PaymentController::class, 'confirmPayment']); 
    
    Route::delete('appointments/{appointment_id}', [AppointmentController::class, 'destroy']);
   
   Route::prefix('admin')->group(function () { 
        Route::get('appointments', [AdminController::class, 'getAppointments']);
        Route::get('dashboard-stats', [AdminController::class, 'getDashboardStats']); 
    });
});