<?php

use App\Http\Controllers\Admin\AdminAuthenticationController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminCompanySettingsController;
use App\Http\Controllers\Admin\AdminContactController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\AdminLandingCmsController;
use App\Http\Controllers\Admin\AdminListingController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminPermissionController;
use App\Http\Controllers\Admin\AdminRatingController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Client\ClientAuthenticationController;
use App\Http\Controllers\Client\ClientBookingController;
use App\Http\Controllers\Client\ClientContactController;
use App\Http\Controllers\Client\ClientPaymentController;
use App\Http\Controllers\Client\ClientRatingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LandingCmsController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('listings', [ListingController::class, 'index']);
Route::get('listings/random', [ListingController::class, 'random']);
Route::get('listings/{listing}/reviews', [ListingController::class, 'reviews']);
Route::get('listings/{listing}', [ListingController::class, 'show']);
Route::post('contacts', [ContactController::class, 'store']);
Route::get('landing-cms', [LandingCmsController::class, 'show']);

Route::get('payment/callback', [PaymentController::class, 'callback']);
Route::get('payment/verify', [PaymentController::class, 'verify']);
Route::get('payment/webhook', [PaymentController::class, 'webhook']);

Route::prefix('client')->group(function () {
    Route::post('login', [ClientAuthenticationController::class, 'login']);
    Route::post('register', [ClientAuthenticationController::class, 'register']);
    Route::post('verify-otp', [ClientAuthenticationController::class, 'verifyOtp']);
    Route::post('resend-otp', [ClientAuthenticationController::class, 'resendOtp']);

    Route::middleware('auth:client-api')->group(function () {
        Route::post('logout', [ClientAuthenticationController::class, 'logout']);
        Route::post('update-profile', [ClientAuthenticationController::class, 'updateProfile']);

        Route::get('bookings', [ClientBookingController::class, 'index']);
        Route::get('bookings/{booking}', [ClientBookingController::class, 'show']);
        Route::post('bookings', [ClientBookingController::class, 'store']);
        Route::put('bookings/{booking}', [ClientBookingController::class, 'update']);
        Route::delete('bookings/{booking}', [ClientBookingController::class, 'destroy']);

        Route::get('payments', [ClientPaymentController::class, 'index']);
        Route::get('payments/{payment}', [ClientPaymentController::class, 'show']);
        Route::post('payments/{payment}/retry', [ClientPaymentController::class, 'retry']);

        Route::get('ratings', [ClientRatingController::class, 'index']);
        Route::post('ratings', [ClientRatingController::class, 'store']);
        Route::get('ratings/{rating}', [ClientRatingController::class, 'show']);
        Route::put('ratings/{rating}', [ClientRatingController::class, 'update']);
        Route::delete('ratings/{rating}', [ClientRatingController::class, 'destroy']);

        Route::get('enquiries', [ClientContactController::class, 'index']);
        Route::get('enquiries/{enquiry}', [ClientContactController::class, 'show']);
    });
});

Route::prefix('admin')->group(function () {
    Route::post('login', [AdminAuthenticationController::class, 'login']);
    Route::post('verify-otp', [AdminAuthenticationController::class, 'verifyOtp']);
    Route::post('resend-otp', [AdminAuthenticationController::class, 'resendOtp']);

    Route::middleware('auth:admin-api')->group(function () {
        Route::post('logout', [AdminAuthenticationController::class, 'logout']);
        Route::post('update-profile', [AdminAuthenticationController::class, 'updateProfile']);

        Route::middleware('admin.permission:listing_management')->group(function () {
            Route::get('listings', [AdminListingController::class, 'index']);
            Route::get('listings/{listing}', [AdminListingController::class, 'show']);
            Route::post('listings', [AdminListingController::class, 'store']);
            Route::put('listings/{listing}', [AdminListingController::class, 'update']);
            Route::patch('listings/{listing}/status', [AdminListingController::class, 'updateStatus']);
            Route::delete('listings/{listing}', [AdminListingController::class, 'destroy']);
        });

        Route::middleware('admin.permission:booking_management')->group(function () {
            Route::get('bookings', [AdminBookingController::class, 'index']);
            Route::get('bookings/{booking}', [AdminBookingController::class, 'show']);
            Route::post('bookings', [AdminBookingController::class, 'store']);
            Route::put('bookings/{booking}', [AdminBookingController::class, 'update']);
            Route::delete('bookings/{booking}', [AdminBookingController::class, 'destroy']);
        });

        Route::middleware('admin.permission:client_management')->group(function () {
            Route::get('clients', [AdminClientController::class, 'index']);
            Route::get('clients/{client}', [AdminClientController::class, 'show']);
        });

        Route::middleware('admin.permission:user_management')->group(function () {
            Route::get('admins', [AdminUserController::class, 'index']);
            Route::get('admins/{admin}', [AdminUserController::class, 'show']);
            Route::post('admins', [AdminUserController::class, 'store']);
            Route::put('admins/{admin}', [AdminUserController::class, 'update']);
            Route::delete('admins/{admin}', [AdminUserController::class, 'destroy']);
        });

        Route::middleware('admin.permission:role_management')->group(function () {
            Route::get('roles', [AdminRoleController::class, 'index']);
            Route::get('roles/{role}', [AdminRoleController::class, 'show']);
            Route::post('roles', [AdminRoleController::class, 'store']);
            Route::put('roles/{role}', [AdminRoleController::class, 'update']);
            Route::delete('roles/{role}', [AdminRoleController::class, 'destroy']);
            Route::get('permissions', [AdminPermissionController::class, 'index']);
            Route::get('permissions/{permission}', [AdminPermissionController::class, 'show']);
            Route::put('permissions/{permission}', [AdminPermissionController::class, 'update']);
        });

        Route::middleware('admin.permission:contact_management')->group(function () {
            Route::get('contacts', [AdminContactController::class, 'index']);
            Route::get('contacts/{contact}', [AdminContactController::class, 'show']);
            Route::put('contacts/{contact}', [AdminContactController::class, 'update']);
            Route::delete('contacts/{contact}', [AdminContactController::class, 'destroy']);
        });

        Route::get('payments', [AdminPaymentController::class, 'index']);
        Route::get('payments/{payment}', [AdminPaymentController::class, 'show']);
        Route::post('payments', [AdminPaymentController::class, 'store']);

        Route::get('ratings', [AdminRatingController::class, 'index']);
        Route::patch('ratings/{rating}', [AdminRatingController::class, 'update']);

        Route::get('invoices', [AdminInvoiceController::class, 'index']);
        Route::post('invoices', [AdminInvoiceController::class, 'store']);
        Route::get('invoices/{invoice}', [AdminInvoiceController::class, 'show']);
        Route::put('invoices/{invoice}', [AdminInvoiceController::class, 'update']);
        Route::delete('invoices/{invoice}', [AdminInvoiceController::class, 'destroy']);
        Route::post('invoices/{invoice}/send', [AdminInvoiceController::class, 'send']);

        Route::get('company-settings', [AdminCompanySettingsController::class, 'show']);
        Route::post('company-settings', [AdminCompanySettingsController::class, 'store']);

        Route::get('landing-cms', [AdminLandingCmsController::class, 'show']);
        Route::put('landing-cms', [AdminLandingCmsController::class, 'updateDraft']);
        Route::post('landing-cms/publish', [AdminLandingCmsController::class, 'publish']);
        Route::post('landing-cms/reset', [AdminLandingCmsController::class, 'reset']);
    });
});
