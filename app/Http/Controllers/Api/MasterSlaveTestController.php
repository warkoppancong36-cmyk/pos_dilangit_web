<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TransactionLogMasterSlave;
use App\Traits\MasterSlaveTrait;
use App\Services\SimpleDbService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MasterSlaveTestController extends Controller
{
    use MasterSlaveTrait;

    /**
     * Test database connections
     */
    public function testConnections(): JsonResponse
    {
        try {
            $connections = $this->testDbConnections();
            
            return response()->json([
                'success' => true,
                'message' => 'Database connection test completed',
                'data' => $connections
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create test transaction log (Master write)
     */
    public function createTestLog(Request $request): JsonResponse
    {
        try {
            $logData = [
                'transaction_id' => uniqid('test_'),
                'method' => $request->method(),
                'endpoint' => '/api/master-slave-test/create',
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
                'username' => auth()->user()->name ?? 'guest',
                'user_role' => auth()->user()->role ?? 'guest',
                'request_headers' => json_encode($request->headers->all()),
                'request_payload' => json_encode($request->all()),
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'response_status' => 201,
                'response_payload' => json_encode(['message' => 'Test log created']),
                'execution_time' => rand(50, 500) / 1000, // Random execution time
                'memory_usage' => memory_get_peak_usage(),
                'session_id' => session()->getId(),
                'device_type' => 'desktop',
                'browser' => 'unknown',
                'platform' => 'unknown',
                'transaction_type' => 'create',
                'module' => 'test',
                'action' => 'create_test_log',
                'entity_type' => 'transaction_log',
                'is_successful' => true,
                'created_at' => now(),
                'created_date' => now()->toDateString()
            ];

            // Create using master
            $logId = TransactionLogMasterSlave::createLog($logData);

            $this->logMasterSlaveOperation('CREATE', 'transaction_logs', ['id' => $logId]);

            return response()->json([
                'success' => true,
                'message' => 'Test log created successfully',
                'data' => [
                    'log_id' => $logId,
                    'transaction_id' => $logData['transaction_id'],
                    'created_via' => 'master'
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test log',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction logs (Slave read)
     */
    public function getLogs(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['user_id', 'module', 'is_successful', 'date_from', 'date_to']);
            $limit = $request->input('limit', 50);

            // Read from slave
            $logs = TransactionLogMasterSlave::getLogs($filters, $limit);

            $this->logMasterSlaveOperation('READ', 'transaction_logs', ['filters' => $filters]);

            return response()->json([
                'success' => true,
                'message' => 'Logs retrieved successfully',
                'data' => [
                    'logs' => $logs,
                    'count' => $logs->count(),
                    'filters_applied' => $filters,
                    'read_from' => 'slave'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get consistent logs from master
     */
    public function getConsistentLogs(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['user_id', 'module', 'is_successful', 'date_from', 'date_to']);
            $limit = $request->input('limit', 50);

            // Force read from master for consistency
            $logs = TransactionLogMasterSlave::getConsistentLogs($filters, $limit);

            $this->logMasterSlaveOperation('READ_MASTER', 'transaction_logs', ['filters' => $filters]);

            return response()->json([
                'success' => true,
                'message' => 'Consistent logs retrieved successfully',
                'data' => [
                    'logs' => $logs,
                    'count' => $logs->count(),
                    'filters_applied' => $filters,
                    'read_from' => 'master'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve consistent logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get analytics data (Slave read)
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $groupBy = $request->input('group_by', 'module');
            $dateRange = $request->input('date_range', '7 days');

            // Analytics from slave
            $analytics = TransactionLogMasterSlave::getAnalytics($groupBy, $dateRange);

            $this->logMasterSlaveOperation('ANALYTICS', 'transaction_logs', [
                'group_by' => $groupBy,
                'date_range' => $dateRange
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Analytics data retrieved successfully',
                'data' => [
                    'analytics' => $analytics,
                    'group_by' => $groupBy,
                    'date_range' => $dateRange,
                    'read_from' => 'slave'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve analytics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk create test logs
     */
    public function bulkCreateTestLogs(Request $request): JsonResponse
    {
        try {
            $count = $request->input('count', 10);
            $logsData = [];

            for ($i = 0; $i < $count; $i++) {
                $logsData[] = [
                    'transaction_id' => uniqid('bulk_test_'),
                    'method' => 'POST',
                    'endpoint' => '/api/master-slave-test/bulk',
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                    'username' => auth()->user()->name ?? 'guest',
                    'user_role' => auth()->user()->role ?? 'guest',
                    'request_headers' => json_encode(['Content-Type' => 'application/json']),
                    'request_payload' => json_encode(['batch' => $i + 1]),
                    'request_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'response_status' => 201,
                    'response_payload' => json_encode(['message' => 'Bulk log ' . ($i + 1) . ' created']),
                    'execution_time' => rand(10, 100) / 1000,
                    'memory_usage' => memory_get_peak_usage(),
                    'session_id' => session()->getId(),
                    'device_type' => 'desktop',
                    'browser' => 'unknown',
                    'platform' => 'unknown',
                    'transaction_type' => 'bulk_create',
                    'module' => 'test',
                    'action' => 'bulk_create_test_logs',
                    'entity_type' => 'transaction_log',
                    'is_successful' => true,
                    'created_at' => now(),
                    'created_date' => now()->toDateString()
                ];
            }

            // Bulk create using master
            $result = TransactionLogMasterSlave::bulkCreateLogs($logsData);

            $this->logMasterSlaveOperation('BULK_CREATE', 'transaction_logs', ['count' => $count]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk test logs created successfully',
                'data' => [
                    'logs_created' => $count,
                    'result' => $result,
                    'created_via' => 'master'
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create bulk test logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get database statistics
     */
    public function getDatabaseStats(): JsonResponse
    {
        try {
            $stats = $this->getTableStats();

            return response()->json([
                'success' => true,
                'message' => 'Database statistics retrieved successfully',
                'data' => [
                    'table_stats' => $stats,
                    'read_from' => 'master'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve database stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get error logs
     */
    public function getErrorLogs(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 50);

            // Get error logs from slave
            $errorLogs = TransactionLogMasterSlave::getErrorLogs($limit);

            return response()->json([
                'success' => true,
                'message' => 'Error logs retrieved successfully',
                'data' => [
                    'error_logs' => $errorLogs,
                    'count' => $errorLogs->count(),
                    'read_from' => 'slave'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve error logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
