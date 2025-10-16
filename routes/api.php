<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PatientController;

// Authentication
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/staff', [AdminController::class, 'index']);
    Route::post('/admin/staff', [AdminController::class, 'store']);
    Route::put('/admin/staff/{id}', [AdminController::class, 'update']);
    Route::delete('/admin/staff/{id}', [AdminController::class, 'destroy']);
});

// Nurse routes
Route::middleware(['auth:sanctum', 'role:nurse'])->group(function () {
    Route::get('/nurses', [NurseController::class, 'index']);
    Route::post('/nurses', [NurseController::class, 'store']);
    Route::put('/nurses/{id}', [NurseController::class, 'update']);
    Route::delete('/nurses/{id}', [NurseController::class, 'destroy']);

    // 🩺 Patient management (Nurse only)
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    Route::delete('/patients/{id}', [PatientController::class, 'destroy']);

    // Nurse appointments
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/done', [AppointmentController::class, 'doneAppointments']);
    Route::delete('/appointments/done/clear', [AppointmentController::class, 'clearDoneAppointments']);
});

// Doctor routes
Route::middleware(['auth:sanctum', 'role:doctor'])->group(function () {
    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::post('/doctors', [DoctorController::class, 'store']);
    Route::put('/doctors/{id}', [DoctorController::class, 'update']);
    Route::delete('/doctors/{id}', [DoctorController::class, 'destroy']);

    // Doctor updates appointment
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
});

// Shared routes (Nurse + Doctor)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
});
