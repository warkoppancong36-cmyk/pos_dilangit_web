<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokenExpiration
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated with Sanctum
        if ($request->bearerToken()) {
            $token = PersonalAccessToken::findToken($request->bearerToken());
            
            if ($token) {
                // Check if token is expired
                if ($token->expires_at && $token->expires_at->isPast()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Token has expired',
                        'error' => 'TOKEN_EXPIRED',
                        'expired_at' => $token->expires_at->toISOString(),
                        'current_time' => now()->toISOString()
                    ], 401);
                }
                
                // Check if token will expire soon (within 10 minutes)
                if ($token->expires_at && $token->expires_at->diffInMinutes(now()) <= 10) {
                    // Add header to indicate token will expire soon
                    $response = $next($request);
                    $response->headers->set('X-Token-Expires-Soon', 'true');
                    $response->headers->set('X-Token-Expires-At', $token->expires_at->toISOString());
                    $response->headers->set('X-Token-Expires-In', $token->expires_at->diffInSeconds(now()));
                    
                    return $response;
                }
            }
        }

        return $next($request);
    }
}
