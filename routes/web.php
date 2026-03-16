<?php

use App\Http\Controllers\Auth\WebAuthController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\EmployeeDashboardController;
use App\Http\Controllers\Dashboard\ExecutiveDashboardController;
use App\Http\Controllers\Dashboard\HelpdeskDashboardController;
use App\Http\Controllers\Dashboard\TechnicianDashboardController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', fn() => redirect('/login'));

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [WebAuthController::class, 'login']);
});

// Logout
Route::middleware('auth')->post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Employee routes
Route::middleware(['auth', 'role:Employee'])->prefix('employee')->group(function () {
    Route::get('/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
});

// Helpdesk routes
Route::middleware(['auth', 'role:Helpdesk'])->prefix('helpdesk')->group(function () {
    Route::get('/dashboard', [HelpdeskDashboardController::class, 'index'])->name('helpdesk.dashboard');
});

// IT Technician routes
Route::middleware(['auth', 'role:IT Technician'])->prefix('technician')->group(function () {
    Route::get('/dashboard', [TechnicianDashboardController::class, 'index'])->name('technician.dashboard');
});

// IT Admin routes
Route::middleware(['auth', 'role:IT Admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

// Executive routes
Route::middleware(['auth', 'role:Executive'])->prefix('executive')->group(function () {
    Route::get('/dashboard', [ExecutiveDashboardController::class, 'index'])->name('executive.dashboard');
});

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
});