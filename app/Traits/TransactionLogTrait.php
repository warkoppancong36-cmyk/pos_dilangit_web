<?php

namespace App\Traits;

use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait TransactionLogTrait
{
    /**
     * Log transaction activity
     */
    protected function logTransaction(
        Request $request,
        string $action,
        string $entityType,
        $entityId = null,
        bool $isSuccessful = true,
        string $errorMessage = null,
        array $additionalData = [],
        $response = null
    ): void {
        try {
            $user = Auth::user();
            $startTime = $request->server('REQUEST_TIME_FLOAT') ?? microtime(true);
            $executionTime = microtime(true) - $startTime;
            
            // Generate unique transaction ID
            $transactionId = Str::uuid()->toString();
            
            // Determine module from controller class
            $module = $this->getModuleFromController();
            
            // Parse user agent for device info
            $deviceInfo = $this->parseUserAgent($request->userAgent());
            
            // Get response data
            $responseData = $this->getResponseData($response);
            
            // Prepare log data
            $logData = [
                'transaction_id' => $transactionId,
                'method' => $request->getMethod(),
                'endpoint' => $request->getPathInfo(),
                'url' => $request->fullUrl(),
                'user_id' => $user?->id,
                'username' => $user?->username ?? $user?->name,
                'user_role' => $user?->role?->name,
                'token_id' => $this->getTokenId($request),
                'request_headers' => $this->limitJsonSize($this->filterHeaders($request->headers->all())),
                'request_payload' => $this->limitJsonSize($this->filterSensitiveData($request->all())),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'response_status' => $responseData['status'],
                'response_payload' => $this->limitJsonSize($responseData['payload']),
                'response_headers' => $this->limitJsonSize($responseData['headers']),
                'execution_time' => round($executionTime * 1000, 3), // Convert to milliseconds
                'memory_usage' => memory_get_peak_usage(true),
                'session_id' => session()->getId(),
                'device_type' => $deviceInfo['device_type'],
                'browser' => $deviceInfo['browser'],
                'platform' => $deviceInfo['platform'],
                'location' => $this->getLocationFromIP($request->ip()),
                'transaction_type' => $this->getTransactionType($action),
                'module' => $module,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'is_successful' => $isSuccessful,
                'error_message' => $errorMessage ? substr($errorMessage, 0, 1000) : null, // Limit error message
                'stack_trace' => $isSuccessful ? null : $this->limitJsonSize(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)),
                'created_at' => now(),
                'completed_at' => now(),
            ];
            
            // Merge additional data
            $logData = array_merge($logData, $additionalData);

            // Create transaction log
            $logRecord = TransactionLog::create($logData);
            
            // Optional: Check if the log was inserted
            if (!$logRecord || !$logRecord->exists) {
                \Log::warning('Transaction log was not inserted into the table.', ['logData' => $logData]);
            }

        } catch (\Exception $e) {
            // Silent fail - logging should not break the application
            \Log::error('Failed to log transaction: ' . $e->getMessage(), [
                'action' => $action ?? 'unknown',
                'entity_type' => $entityType ?? 'unknown',
                'error' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Get module name from controller class
     */
    protected function getModuleFromController(): string
    {
        $className = class_basename(static::class);
        return strtolower(str_replace('Controller', '', $className));
    }
    
    /**
     * Get transaction type based on action
     */
    protected function getTransactionType(string $action): string
    {
        $types = [
            'index' => 'read',
            'show' => 'read',
            'store' => 'create',
            'update' => 'update',
            'destroy' => 'delete',
            'delete' => 'delete',
            'toggle' => 'update',
            'bulk' => 'bulk_operation',
            'stats' => 'read',
        ];
        
        foreach ($types as $actionPattern => $type) {
            if (str_contains(strtolower($action), $actionPattern)) {
                return $type;
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Parse user agent for device information
     */
    protected function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return [
                'device_type' => 'unknown',
                'browser' => 'unknown',
                'platform' => 'unknown'
            ];
        }
        
        // Simple user agent parsing
        $deviceType = 'desktop';
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
            $deviceType = 'tablet';
        }
        
        $browser = 'unknown';
        if (preg_match('/Chrome/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/', $userAgent)) {
            $browser = 'Edge';
        }
        
        $platform = 'unknown';
        if (preg_match('/Windows/', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/Mac/', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/Android/', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/iOS/', $userAgent)) {
            $platform = 'iOS';
        }
        
        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'platform' => $platform
        ];
    }
    
    /**
     * Filter sensitive data from request
     */
    protected function filterSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'private_key',
            'credit_card',
            'ssn'
        ];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }
        
        return $data;
    }
    
    /**
     * Filter headers to remove sensitive information
     */
    protected function filterHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token'
        ];
        
        foreach ($sensitiveHeaders as $header) {
            if (isset($headers[$header])) {
                $headers[$header] = ['[FILTERED]'];
            }
        }
        
        return $headers;
    }
    
    /**
     * Get token ID from request
     */
    protected function getTokenId(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7, 10) . '...'; // First 10 chars for identification
        }
        
        return null;
    }
    
    /**
     * Get response data
     */
    protected function getResponseData($response): array
    {
        if (!$response) {
            return [
                'status' => 200,
                'payload' => null,
                'headers' => []
            ];
        }
        
        $payload = null;
        $headers = [];
        $status = 200;
        
        if (method_exists($response, 'getStatusCode')) {
            $status = $response->getStatusCode();
        }
        
        if (method_exists($response, 'headers')) {
            $headers = $response->headers->all();
        }
        
        if (method_exists($response, 'getContent')) {
            $content = $response->getContent();
            $payload = json_decode($content, true) ?? $content;
        }
        
        return [
            'status' => $status,
            'payload' => $payload,
            'headers' => $this->filterHeaders($headers)
        ];
    }
    
    /**
     * Get approximate location from IP (placeholder)
     */
    protected function getLocationFromIP(string $ip): ?string
    {
        // In a real application, you might use a GeoIP service
        // For now, just return a placeholder
        return $ip === '127.0.0.1' ? 'localhost' : 'unknown';
    }
    
    /**
     * Limit JSON size to prevent database issues
     */
    protected function limitJsonSize($data, int $maxSize = 10000): mixed
    {
        if (is_null($data)) {
            return null;
        }
        
        $json = json_encode($data);
        if (strlen($json) > $maxSize) {
            // If too large, try to truncate arrays/objects
            if (is_array($data)) {
                return ['truncated' => 'Data too large, showing first few items', 'data' => array_slice($data, 0, 5)];
            } elseif (is_string($data)) {
                return substr($data, 0, $maxSize) . '... [truncated]';
            } else {
                return ['truncated' => 'Data too large to log'];
            }
        }
        
        return $data;
    }
    
    /**
     * Log successful transaction
     */
    protected function logSuccess(Request $request, string $action, string $entityType, $entityId = null, $response = null): void
    {
        $this->logTransaction($request, $action, $entityType, $entityId, true, null, [], $response);
    }
    
    /**
     * Log failed transaction
     */
    protected function logError(Request $request, string $action, string $entityType, string $errorMessage, $entityId = null): void
    {
        $this->logTransaction($request, $action, $entityType, $entityId, false, $errorMessage);
    }
    
    /**
     * Simple test method to check if basic logging works
     */
    protected function testBasicLog(): void
    {
        try {
            $testData = [
                'transaction_id' => Str::uuid()->toString(),
                'method' => 'TEST',
                'endpoint' => '/test',
                'url' => 'http://test.com',
                'user_id' => auth()->id(),
                'username' => auth()->user()?->username ?? 'test',
                'user_role' => 'test',
                'module' => 'test',
                'action' => 'test',
                'entity_type' => 'test',
                'is_successful' => true,
                'created_at' => now(),
                'completed_at' => now(),
            ];
            
            $result = TransactionLog::create($testData);
            \Log::info('Test log created', ['result' => $result?->id]);
            
        } catch (\Exception $e) {
            \Log::error('Test log failed: ' . $e->getMessage());
            dd('Test failed', $e->getMessage(), $e->getTraceAsString());
        }
    }
}
