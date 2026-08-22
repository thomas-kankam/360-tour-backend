<?php

use App\Http\Cors;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Rating;
use App\Models\Tour;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::bind('listing', fn (string $value) => Tour::query()->where('tour_slug', $value)->firstOrFail());
            Route::bind('booking', fn (string $value) => Booking::query()->where('booking_code', $value)->firstOrFail());
            Route::bind('rating', fn (string $value) => Rating::query()->where('rating_uuid', $value)->firstOrFail());
            Route::bind('invoice', fn (string $value) => Invoice::query()->where('invoice_uuid', $value)->firstOrFail());
            Route::bind('enquiry', fn (string $value) => Contact::query()->whereKey($value)->firstOrFail());
            Route::bind('operator', fn (string $value) => Admin::query()->where('admin_slug', $value)->firstOrFail());
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->remove([
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\TrustProxies::class,
        ]);

        $middleware->use([
            \App\Http\Middleware\AddCorsHeaders::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'admin.permission' => \App\Http\Middleware\EnsureAdminPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return Cors::apply(response()->json([
                    'data' => [
                        'status_code' => '413',
                        'message' => 'Payload Too Large',
                        'in_error' => true,
                        'reason' => 'Request body exceeds the server limit (post_max_size). Upload images one at a time via POST /api/admin/uploads/images, then send the returned URLs in the tour payload. On the server, raise post_max_size and client_max_body_size (recommended: 64M).',
                        'data' => [],
                        'point_in_time' => now()->toIso8601String(),
                    ],
                ], 413), $request);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($e instanceof PostTooLargeException) {
                return null;
            }

            $payload = [
                'data' => [
                    'status_code' => '500',
                    'message' => config('app.debug') ? $e->getMessage() : 'Server Error',
                    'in_error' => true,
                    'reason' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred.',
                    'data' => config('app.debug') ? [
                        'exception' => $e::class,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ] : [],
                    'point_in_time' => now()->toIso8601String(),
                ],
            ];

            return Cors::apply(response()->json($payload, 500), $request);
        });
    })->create();
