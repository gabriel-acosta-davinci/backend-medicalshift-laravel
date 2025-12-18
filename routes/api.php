<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GestionController;
use App\Http\Controllers\Api\FacturaController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\CartillaController;

// Middleware para loguear requests (excepto admin)
Route::middleware(['log.requests'])->group(function () {
    // Ruta de prueba
    Route::get('/', function () {
        return response()->json(['message' => 'Servidor funcionando correctamente 🚀']);
    });
});

// Rutas de autenticación (públicas)
Route::prefix('auth')->middleware('log.requests')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify', [AuthController::class, 'verifyToken']);
    Route::post('/recovery', [AuthController::class, 'recovery']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Ruta de verificación de email (pública, sin autenticación)
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->name('verification.verify')
    ->middleware(['signed', 'log.requests']);

// Rutas de autenticación (protegidas)
Route::prefix('auth')->middleware(['auth:api', 'log.requests'])->group(function () {
    Route::get('/me', [AuthController::class, 'getCurrentUser']);
    Route::put('/password', [AuthController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/digital-token', [AuthController::class, 'getDigitalToken']);
    Route::post('/verify-email', [AuthController::class, 'sendVerificationEmail']);
    Route::get('/notifications', [AuthController::class, 'getNotifications']);
});

// Rutas de usuarios
Route::prefix('users')->middleware(['auth:api', 'log.requests'])->group(function () {
    Route::put('/me', [UserController::class, 'updateCurrentUser']);
});

Route::prefix('users')->middleware('log.requests')->group(function () {
    Route::get('/', [UserController::class, 'getAllUsers']);
    Route::get('/{id}', [UserController::class, 'getUserById']);
    Route::post('/', [UserController::class, 'createUser']);
    Route::put('/{id}', [UserController::class, 'updateUser']);
    Route::delete('/{id}', [UserController::class, 'deleteUser']);
});

// Rutas de gestiones (protegidas)
Route::prefix('gestiones')->middleware(['auth:api', 'log.requests'])->group(function () {
    Route::post('/', [GestionController::class, 'create']);
    Route::get('/', [GestionController::class, 'list']);
    Route::delete('/{id}', [GestionController::class, 'delete']);
    
    // Rutas para documentos de gestiones
    Route::post('/{id}/document', [GestionController::class, 'uploadDocument']);
    Route::get('/{id}/document', [GestionController::class, 'downloadDocument']);
    Route::delete('/{id}/document', [GestionController::class, 'deleteDocument']);
});

// Rutas de facturas (protegidas)
Route::prefix('facturas')->middleware(['auth:api', 'log.requests'])->group(function () {
    Route::get('/', [FacturaController::class, 'list']);
});

// Rutas de storage/documentos (protegidas)
Route::prefix('storage')->middleware(['auth:api', 'log.requests'])->group(function () {
    Route::get('/documents', [GestionController::class, 'listDocuments']);
    Route::get('/url/{fileName}', [GestionController::class, 'getDocumentUrl']);
});

// Rutas de cartilla (públicas)
Route::prefix('cartilla')->middleware('log.requests')->group(function () {
    Route::get('/provinces', [CartillaController::class, 'getProvinces']);
    Route::get('/localidades', [CartillaController::class, 'getLocalidades']);
    Route::get('/specialties', [CartillaController::class, 'getSpecialties']);
    Route::get('/providers', [CartillaController::class, 'searchProviders']);
    Route::get('/providers-grouped', [CartillaController::class, 'searchProvidersGrouped']);
    Route::get('/professionals', [CartillaController::class, 'searchProfessionals']);
    Route::get('/pharmacies', [CartillaController::class, 'searchPharmacies']);
    Route::get('/vaccines', [CartillaController::class, 'searchVaccines']);
});

