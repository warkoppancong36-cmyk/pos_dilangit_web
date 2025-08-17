<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;

class AuthController extends BaseApiController
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users|alpha_dash',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id ?? 3,
        ]);

        $user->load('role');
        
        // Create token with expiration
        $tokenName = 'auth_token';
        $expirationMinutes = (int) config('sanctum.expiration', 60);
        $expiresAt = now()->addMinutes($expirationMinutes);
        $token = $user->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;
        
        $this->logUserActivity($user, 'login', true, 'Registration');

        return $this->registrationSuccessResponse($user, $token);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::with('role')->where($loginField, $request->login)->first();

        if (!$user) {
            $this->logFailedLogin($request->login, 'User not found');
            return $this->invalidCredentialsResponse('Invalid login credentials');
        }

        if ($user->isLocked()) {
            $this->logFailedLogin($request->login, 'Account locked');
            return $this->accountLockedResponse('Account is locked until ' . $user->locked_until->format('Y-m-d H:i:s'));
        }

        if (!$user->is_active) {
            $this->logFailedLogin($request->login, 'Account inactive');
            return $this->accountInactiveResponse();
        }

        if (!Hash::check($request->password, $user->password)) {
            $user->incrementLoginAttempts();
            if ($user->login_attempts >= 5) {
                $user->lockAccount(30);
                $this->logFailedLogin($request->login, 'Account locked after 5 failed attempts');
                return $this->accountLockedResponse('Account locked after 5 failed login attempts');
            }
            $this->logFailedLogin($request->login, 'Invalid password');
            return $this->invalidCredentialsResponse('Invalid login credentials');
        }

        $user->resetLoginAttempts();
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'last_login_device' => $this->getDeviceInfo($request),
        ]);

        // Create token with expiration
        $tokenName = 'auth_token';
        $expirationMinutes = (int) config('sanctum.expiration', 60);
        $expiresAt = now()->addMinutes($expirationMinutes);
        $token = $user->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;
        
        // Log successful login
        $this->logUserActivity($user, 'login', true);

        return $this->loginSuccessResponse($user, $token);
    }

    public function profile(Request $request)
    {
        $user = $request->user()->load('role');
        return $this->profileResponse($user);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $this->logUserActivity($user, 'logout', true);
        $request->user()->currentAccessToken()->delete();

        return $this->logoutSuccessResponse();
    }

    public function logoutAll(Request $request)
    {
        $user = $request->user();
        $this->logUserActivity($user, 'logout', true, 'Logged out from all devices');
        $request->user()->tokens()->delete();

        return $this->logoutSuccessResponse('Logged out from all devices successfully');
    }

    public function refresh(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();
        
        // Create new token with expiration
        $tokenName = 'auth_token';
        $expirationMinutes = (int) config('sanctum.expiration', 60);
        $expiresAt = now()->addMinutes($expirationMinutes);
        $token = $user->createToken($tokenName, ['*'], $expiresAt)->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toISOString()
        ], 'Token refreshed successfully');
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse('Current password is incorrect', null, 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->successResponse(null, 'Password changed successfully');
    }

    private function logUserActivity(User $user, string $loginType, bool $isSuccessful, string $reason = null)
    {
        $agent = new Agent();

        UserLoginLog::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'login_type' => $loginType,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => $agent->device(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'is_successful' => $isSuccessful,
            'failure_reason' => $reason,
            'login_at' => now(),
        ]);
    }

    private function logFailedLogin(string $login, string $reason)
    {
        $agent = new Agent();

        UserLoginLog::create([
            'user_id' => null,
            'username' => $login,
            'login_type' => 'failed_login',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => $agent->device(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'is_successful' => false,
            'failure_reason' => $reason,
            'login_at' => now(),
        ]);
    }

    private function getDeviceInfo(Request $request): string
    {
        $agent = new Agent();
        return $agent->device() . ' - ' . $agent->platform() . ' - ' . $agent->browser();
    }
}
