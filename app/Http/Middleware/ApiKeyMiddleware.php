<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'API Key is missing'], 400);
        }

        if ($apiKey !== config('app.api_key')) {
            return response()->json(['error' => 'Invalid API Key'], 401);
        }

        return $next($request);
    }
}