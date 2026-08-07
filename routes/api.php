<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrgController;
use App\Http\Controllers\Api\TicketsController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
});

// Read-only org/user directory for other in-house systems — separate 'org:read'
// token ability (issued via `php artisan api:issue-org-token`), not tied to the
// session-based web roles used elsewhere in this app.
Route::middleware(['auth:sanctum', 'abilities:org:read'])->prefix('org')->group(function () {
    Route::get('/business-units', [OrgController::class, 'businessUnits']);
    Route::get('/companies', [OrgController::class, 'companies']);
    Route::get('/departments', [OrgController::class, 'departments']);
    Route::get('/users', [OrgController::class, 'users']);
});

// Read-only ticket export for SLA/workload/resolution-time analysis — separate
// 'tickets:read' token ability (issued via `php artisan api:issue-tickets-token`),
// scoped independently from 'org:read' so access can be granted per-resource.
Route::middleware(['auth:sanctum', 'abilities:tickets:read'])->group(function () {
    Route::get('/tickets', [TicketsController::class, 'index']);
});