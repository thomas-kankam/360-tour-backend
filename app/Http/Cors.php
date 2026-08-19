<?php

namespace App\Http;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public static function apply(Response $response, Request $request): Response
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigins = config('cors.allowed_origins', ['*']);

        if ($allowedOrigins === []) {
            $allowedOrigins = ['*'];
        }

        if (in_array('*', $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        } elseif ($origin && in_array($origin, $allowedOrigins, true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->Vary('Origin', false);
        }

        $methods = config('cors.allowed_methods', ['*']);
        $headers = config('cors.allowed_headers', ['*']);

        $response->headers->set(
            'Access-Control-Allow-Methods',
            is_array($methods) ? implode(', ', $methods) : (string) $methods
        );
        $response->headers->set(
            'Access-Control-Allow-Headers',
            is_array($headers) ? implode(', ', $headers) : (string) $headers
        );

        if (config('cors.supports_credentials')) {
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
