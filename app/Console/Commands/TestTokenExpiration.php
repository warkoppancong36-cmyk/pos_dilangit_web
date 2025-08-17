<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class TestTokenExpiration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:token-expiration {--user-id=1 : User ID to test with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test token expiration functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔐 Testing Token Expiration Functionality');
        $this->line('==========================================');

        $userId = $this->option('user-id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("❌ User with ID {$userId} not found!");
            return 1;
        }

        $this->info("👤 Testing with user: {$user->name} ({$user->email})");

        // Test 1: Create tokens with different expiration times
        $this->testTokenCreation($user);

        // Test 2: Check token expiration status
        $this->testTokenStatus($user);

        // Test 3: Test expired token cleanup
        $this->testExpiredTokens($user);

        $this->line('');
        $this->info('✅ Token expiration test completed!');

        return 0;
    }

    private function testTokenCreation($user)
    {
        $this->line('');
        $this->comment('📝 Test 1: Creating tokens with different expiration times');

        // Clean up existing tokens first
        $user->tokens()->delete();

        // Create token with 1 minute expiration
        $shortToken = $user->createToken('short-token', ['*'], Carbon::now()->addMinutes(1));
        $this->info("✓ Short token created (expires in 1 minute): {$shortToken->plainTextToken}");

        // Create token with 1 hour expiration (default)
        $normalToken = $user->createToken('normal-token', ['*'], Carbon::now()->addHours(1));
        $this->info("✓ Normal token created (expires in 1 hour): {$normalToken->plainTextToken}");

        // Create token with no expiration
        $permanentToken = $user->createToken('permanent-token');
        $this->info("✓ Permanent token created (never expires): {$permanentToken->plainTextToken}");

        // Display token details
        $this->table(
            ['Token Name', 'Expires At', 'Status'],
            [
                ['short-token', $shortToken->accessToken->expires_at ?? 'Never', $this->getTokenStatus($shortToken->accessToken)],
                ['normal-token', $normalToken->accessToken->expires_at ?? 'Never', $this->getTokenStatus($normalToken->accessToken)],
                ['permanent-token', $permanentToken->accessToken->expires_at ?? 'Never', $this->getTokenStatus($permanentToken->accessToken)],
            ]
        );
    }

    private function testTokenStatus($user)
    {
        $this->line('');
        $this->comment('🔍 Test 2: Checking token expiration status');

        $tokens = $user->tokens;

        foreach ($tokens as $token) {
            $status = $this->getTokenStatus($token);
            $expiresAt = $token->expires_at ? $token->expires_at->format('Y-m-d H:i:s') : 'Never';
            
            if ($status === 'Expired') {
                $this->error("❌ Token '{$token->name}' is expired (expired at: {$expiresAt})");
            } elseif ($status === 'Expiring Soon') {
                $this->warn("⚠️  Token '{$token->name}' is expiring soon (expires at: {$expiresAt})");
            } else {
                $this->info("✅ Token '{$token->name}' is valid (expires at: {$expiresAt})");
            }
        }
    }

    private function testExpiredTokens($user)
    {
        $this->line('');
        $this->comment('🧹 Test 3: Testing expired token cleanup');

        // Count tokens before cleanup
        $beforeCount = $user->tokens()->count();
        $expiredCount = $user->tokens()->where('expires_at', '<', Carbon::now())->count();

        $this->info("Total tokens before cleanup: {$beforeCount}");
        $this->info("Expired tokens: {$expiredCount}");

        if ($expiredCount > 0) {
            // Delete expired tokens
            $deleted = $user->tokens()->where('expires_at', '<', Carbon::now())->delete();
            $this->info("🗑️  Deleted {$deleted} expired tokens");
        } else {
            $this->info("📝 No expired tokens to clean up");
        }

        // Count tokens after cleanup
        $afterCount = $user->tokens()->count();
        $this->info("Total tokens after cleanup: {$afterCount}");
    }

    private function getTokenStatus($token)
    {
        if (!$token->expires_at) {
            return 'Never Expires';
        }

        $now = Carbon::now();
        $expiresAt = Carbon::parse($token->expires_at);

        if ($expiresAt->isPast()) {
            return 'Expired';
        }

        // Check if token expires within 10 minutes
        if ($expiresAt->diffInMinutes($now) <= 10) {
            return 'Expiring Soon';
        }

        return 'Valid';
    }
}
