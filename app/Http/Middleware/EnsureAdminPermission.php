<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $request->user();

        if (! $admin || ! $admin->hasPermission($permission)) {
            return response()->json([
                'data' => [
                    'status_code' => '403',
                    'message' => 'Action Unsuccessful',
                    'in_error' => true,
                    'reason' => 'You do not have permission to perform this action',
                    'data' => [],
                    'point_in_time' => now(),
                ],
            ], 200);
        }

        return $next($request);
    }
}
