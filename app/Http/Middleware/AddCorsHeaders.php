<?php

namespace App\Http\Middleware;

use App\Http\Cors;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddCorsHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*')) {
            return $next($request);
        }

        if ($request->isMethod('OPTIONS')) {
            return Cors::apply(response('', 204), $request);
        }

        $response = $next($request);

        return Cors::apply($response, $request);
    }
}
