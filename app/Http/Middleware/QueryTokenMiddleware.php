<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class QueryTokenMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('token')) {
            $token = $request->query('token');
            if (!$request->headers->has('Authorization')) {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
            Log::warning('QueryTokenMiddleware: set token', [
                'token' => $token,
                'has_auth_header' => $request->headers->has('Authorization'),
                'auth_header' => $request->headers->get('Authorization'),
            ]);
        }

        return $next($request);
    }
}
