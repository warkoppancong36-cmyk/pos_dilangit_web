<?php

namespace App\Models;

use App\Services\SimpleDbService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class TransactionLogMasterSlave extends Model
{
    use HasFactory;

    /**
     * Default connection (will be overridden based on operation)
     */
    protected $connection = 'mysql';
    
    /**
     * The table associated with the model.
     */
    protected $table = 'transaction_logs';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'transaction_id',
        'method',
        'endpoint',
        'url',
        'user_id',
        'username',
        'user_role',
        'token_id',
        'request_headers',
        'request_payload',
        'request_ip',
        'user_agent',
        'response_status',
        'response_payload',
        'response_headers',
        'execution_time',
        'memory_usage',
        'session_id',
        'device_type',
        'browser',
        'platform',
        'location',
        'transaction_type',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'is_successful',
        'error_message',
        'stack_trace',
        'created_at',
        'created_date',
        'completed_at'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'is_successful' => 'boolean',
        'execution_time' => 'decimal:3',
        'memory_usage' => 'integer',
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_date' => 'date'
    ];

    /**
     * Create new transaction log - always use master
     */
    public static function createLog(array $logData)
    {
        return SimpleDbService::writeQuery(function() use ($logData) {
            // Add created_date for partitioning
            $logData['created_date'] = $logData['created_date'] ?? now()->toDateString();
            
            return DB::connection('mysql_master')
                ->table('transaction_logs')
                ->insertGetId($logData);
        });
    }

    /**
     * Get transaction logs - use slave for read
     */
    public static function getLogs(array $filters = [], int $limit = 100)
    {
        return SimpleDbService::readQuery(function() use ($filters, $limit) {
            $query = DB::connection('mysql_slave_1')
                ->table('transaction_logs')
                ->orderBy('created_at', 'desc')
                ->limit($limit);

            // Apply filters
            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (isset($filters['module'])) {
                $query->where('module', $filters['module']);
            }

            if (isset($filters['is_successful'])) {
                $query->where('is_successful', $filters['is_successful']);
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            return $query->get();
        });
    }

    /**
     * Get consistent logs from master (after write operations)
     */
    public static function getConsistentLogs(array $filters = [], int $limit = 100)
    {
        return SimpleDbService::readFromMaster(function() use ($filters, $limit) {
            $query = DB::connection('mysql_master')
                ->table('transaction_logs')
                ->orderBy('created_at', 'desc')
                ->limit($limit);

            // Apply same filters as getLogs
            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (isset($filters['module'])) {
                $query->where('module', $filters['module']);
            }

            if (isset($filters['is_successful'])) {
                $query->where('is_successful', $filters['is_successful']);
            }

            if (isset($filters['date_from'])) {
                $query->where('created_at', '>=', $filters['date_from']);
            }

            if (isset($filters['date_to'])) {
                $query->where('created_at', '<=', $filters['date_to']);
            }

            return $query->get();
        });
    }

    /**
     * Get analytics data - use slave
     */
    public static function getAnalytics(string $groupBy = 'module', string $dateRange = '7 days')
    {
        return SimpleDbService::readQuery(function() use ($groupBy, $dateRange) {
            return DB::connection('mysql_slave_1')
                ->table('transaction_logs')
                ->select([
                    $groupBy,
                    DB::raw('COUNT(*) as total_requests'),
                    DB::raw('SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful_requests'),
                    DB::raw('SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed_requests'),
                    DB::raw('AVG(execution_time) as avg_execution_time'),
                    DB::raw('MAX(execution_time) as max_execution_time'),
                    DB::raw('AVG(memory_usage) as avg_memory_usage')
                ])
                ->where('created_at', '>=', now()->sub($dateRange))
                ->groupBy($groupBy)
                ->orderBy('total_requests', 'desc')
                ->get();
        });
    }

    /**
     * Bulk create logs - use master
     */
    public static function bulkCreateLogs(array $logsData)
    {
        return SimpleDbService::writeQuery(function() use ($logsData) {
            // Add created_date for all logs
            $logsData = array_map(function($log) {
                $log['created_date'] = $log['created_date'] ?? now()->toDateString();
                return $log;
            }, $logsData);

            return DB::connection('mysql_master')
                ->table('transaction_logs')
                ->insert($logsData);
        });
    }

    /**
     * Find log by transaction_id - prefer slave, fallback to master
     */
    public static function findByTransactionId(string $transactionId)
    {
        return SimpleDbService::readQuery(function() use ($transactionId) {
            return DB::connection('mysql_slave_1')
                ->table('transaction_logs')
                ->where('transaction_id', $transactionId)
                ->first();
        });
    }

    /**
     * Get error logs - use slave
     */
    public static function getErrorLogs(int $limit = 50)
    {
        return SimpleDbService::readQuery(function() use ($limit) {
            return DB::connection('mysql_slave_1')
                ->table('transaction_logs')
                ->where('is_successful', false)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get statistics by date range - use slave
     */
    public static function getStatsByDateRange(string $startDate, string $endDate)
    {
        return SimpleDbService::readQuery(function() use ($startDate, $endDate) {
            return DB::connection('mysql_slave_1')
                ->table('transaction_logs')
                ->select([
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total_requests'),
                    DB::raw('SUM(CASE WHEN is_successful = 1 THEN 1 ELSE 0 END) as successful_requests'),
                    DB::raw('SUM(CASE WHEN is_successful = 0 THEN 1 ELSE 0 END) as failed_requests'),
                    DB::raw('AVG(execution_time) as avg_execution_time')
                ])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'desc')
                ->get();
        });
    }
}
