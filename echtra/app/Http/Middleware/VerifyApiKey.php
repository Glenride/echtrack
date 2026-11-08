<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'error' => 'Missing or invalid Authorization header',
                'message' => 'Please provide a valid API key in the Authorization header as: Bearer YOUR_API_KEY',
            ], 401);
        }

        $token = substr($authHeader, 7);
        $apiKey = ApiKey::findByKey($token);

        if (!$apiKey) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid, expired, or inactive.',
            ], 401);
        }

        // Mark the key as used
        $apiKey->markAsUsed();

        // Attach the API key to the request for use in controllers
        $request->merge(['api_key' => $apiKey]);
        $request->setUserResolver(function () use ($apiKey) {
            return $apiKey->user;
        });

        return $next($request);
    }
}
