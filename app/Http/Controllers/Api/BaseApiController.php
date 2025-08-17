<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;
use App\Traits\TransactionLogTrait;

class BaseApiController extends Controller
{
    use ApiResponseTrait, TransactionLogTrait;

    /**
     * Standard success responses for common operations
     */

    /**
     * Login success response
     */
    protected function loginSuccessResponse($user, $token, string $message = 'Login successful'): \Illuminate\Http\JsonResponse
    {
        $expirationMinutes = (int) config('sanctum.expiration', 60);
        $expiresAt = now()->addMinutes($expirationMinutes);
        
        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'display_name' => $user->role->display_name,
                    'permissions' => $user->role->permissions,
                ],
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expirationMinutes * 60, // seconds
            'expires_at' => $expiresAt->toISOString(),
            'issued_at' => now()->toISOString(),
        ], $message);
    }

    /**
     * Logout success response
     */
    protected function logoutSuccessResponse(string $message = 'Logout successful'): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse(null, $message);
    }

    /**
     * Registration success response
     */
    protected function registrationSuccessResponse($user, $token, string $message = 'Registration successful'): \Illuminate\Http\JsonResponse
    {
        return $this->createdResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'display_name' => $user->role->display_name,
                ],
                'is_active' => $user->is_active,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], $message);
    }

    /**
     * Profile response
     */
    protected function profileResponse($user, string $message = 'Profile retrieved successfully'): \Illuminate\Http\JsonResponse
    {
        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'display_name' => $user->role->display_name,
                    'permissions' => $user->role->permissions,
                ],
                'is_active' => $user->is_active,
                'last_login_at' => $user->last_login_at,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ], $message);
    }

    /**
     * Common error responses
     */

    /**
     * Invalid credentials response
     */
    protected function invalidCredentialsResponse(string $message = 'Invalid credentials'): \Illuminate\Http\JsonResponse
    {
        return $this->unauthorizedResponse($message);
    }

    /**
     * Account locked response
     */
    protected function accountLockedResponse(string $message = 'Account is locked due to multiple failed login attempts'): \Illuminate\Http\JsonResponse
    {
        return $this->forbiddenResponse($message);
    }

    /**
     * Account inactive response
     */
    protected function accountInactiveResponse(string $message = 'Account is inactive. Please contact administrator'): \Illuminate\Http\JsonResponse
    {
        return $this->forbiddenResponse($message);
    }

    /**
     * Token expired response
     */
    protected function tokenExpiredResponse(string $message = 'Token has expired'): \Illuminate\Http\JsonResponse
    {
        return $this->unauthorizedResponse($message);
    }

    /**
     * Insufficient permissions response
     */
    protected function insufficientPermissionsResponse(string $message = 'Insufficient permissions'): \Illuminate\Http\JsonResponse
    {
        return $this->forbiddenResponse($message);
    }
}
