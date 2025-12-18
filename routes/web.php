<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuthController;

// Rutas de autenticación (públicas)
Route::get('/', [AuthController::class, 'showLoginForm'])->name('admin.login.form');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// Rutas de administración (protegidas)
Route::prefix('admin')->middleware([\App\Http\Middleware\AdminMiddleware::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/requests', [DashboardController::class, 'requests'])->name('admin.requests');
    Route::get('/requests/{id}', [DashboardController::class, 'requestDetail'])->name('admin.request-detail');
    Route::get('/migrations', [DashboardController::class, 'migrations'])->name('admin.migrations');
    Route::get('/cache', [DashboardController::class, 'cache'])->name('admin.cache');
    Route::get('/jobs', [DashboardController::class, 'jobs'])->name('admin.jobs');
    Route::post('/cache/clear', [DashboardController::class, 'clearCache'])->name('admin.clear-cache');
    Route::post('/logs/clear', [DashboardController::class, 'clearOldLogs'])->name('admin.clear-old-logs');
});
