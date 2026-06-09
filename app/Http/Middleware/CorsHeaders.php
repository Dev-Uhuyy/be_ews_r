<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin');

        if (! $origin) {
            return $next($request);
        }

        $allowedOrigins = [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            env('FRONTEND_URL', 'http://localhost:3000'),
        ];

        $isAllowed = in_array($origin, $allowedOrigins, true);
        if (! $isAllowed) {
            $isAllowed = (bool) preg_match('#^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$#', $origin);
        }

        $corsOrigin = $isAllowed ? $origin : $allowedOrigins[0];

        if ($request->getMethod() === 'OPTIONS') {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $corsOrigin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->header('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers') ?? 'Authorization, Content-Type, Accept, Origin, Cookie')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400')
                ->header('Vary', 'Origin, Access-Control-Request-Method, Access-Control-Request-Headers');
        }

        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', $corsOrigin);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Expose-Headers', 'Content-Disposition, Content-Type');

        return $response;
    }
}
