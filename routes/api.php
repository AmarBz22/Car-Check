<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PartnerRequestController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;


// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register-admin', [AuthController::class, 'registerAdmin']);
Route::post('/partner-registration-request', [AuthController::class, 'requestPartnerRegistration']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);


// Protected routes
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

// Admin Partner Management Routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/partner-requests', [PartnerRequestController::class, 'getPendingRequests']);
    Route::get('/admin/partner-requests/{id}', [PartnerRequestController::class, 'getRequestDetails']);
    Route::post('/admin/partner-requests/{id}/approve', [PartnerRequestController::class, 'approveRequest']);
    Route::post('/admin/partner-requests/{id}/reject', [PartnerRequestController::class, 'rejectRequest']);

    // Create partner directly without registration request
    Route::post('/admin/partners/create', [PartnerRequestController::class, 'createDirectPartner']);
});

Route::middleware(['auth:sanctum', 'role:admin,partner'])->group(function () {
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::put('/vehicles/{id}', [VehicleController::class, 'update']);
    Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
    Route::post('/vehicles/{vehicle}/images', [VehicleController::class, 'uploadImages']);

});

Route::get('/vehicles', [VehicleController::class, 'index']);

// Listing and showing vehicles can still be for any authenticated user
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/vehicles/{id}', [VehicleController::class, 'show']);

});

Route::post('/payments/create', [PaymentController::class, 'createPayment']);

Route::post('/payments/webhook', [PaymentController::class, 'webhook'])
    ->name('chargily.webhook');

Route::get('/payments/back', [PaymentController::class, 'paymentBack'])
    ->name('reports.payment_back');

// Partner Reports Routes
Route::middleware(['auth:sanctum', 'role:partner'])->group(function () {
    // Create and manage own reports
    Route::post('/partner/reports', [ReportController::class, 'store']);
    Route::get('/partner/reports', [ReportController::class, 'getPartnerReports']);
    Route::put('/partner/reports/{id}', [ReportController::class, 'update']);
    Route::post('/partner/reports/{id}/submit', [ReportController::class, 'submit']);
    Route::delete('/partner/reports/{id}', [ReportController::class, 'destroy']);
});

// View report details (Partner views own, Admin views all)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/{id}', [ReportController::class, 'show']);
});

// Admin Reports Management Routes
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/reports/pending', [ReportController::class, 'getPendingReports']);
    Route::post('/admin/reports/{id}/approve', [ReportController::class, 'approveReport']);
    Route::post('/admin/reports/{id}/reject', [ReportController::class, 'rejectReport']);
});
// Any authenticated user can view partners
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/partners', [PartnerRequestController::class, 'getAllPartners']);
    Route::get('/partners/{id}', [PartnerRequestController::class, 'getPartnerById']);
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::get('/admin/users/{id}', [UserController::class, 'show']);
    Route::put('/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/admin/users/{id}', [UserController::class, 'destroy']);
});
