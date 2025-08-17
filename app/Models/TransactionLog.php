<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'transaction_logs';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'int';

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
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'request_headers' => 'array',
        'request_payload' => 'array',
        'response_headers' => 'array',
        'response_payload' => 'array',
        'stack_trace' => 'array',
        'execution_time' => 'decimal:3',
        'memory_usage' => 'integer',
        'is_successful' => 'boolean',
        'created_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that made the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful transactions
     */
    public function scopeSuccessful($query)
    {
        return $query->where('is_successful', true);
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('is_successful', false);
    }

    /**
     * Scope for specific transaction type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted execution time
     */
    public function getFormattedExecutionTimeAttribute()
    {
        return $this->execution_time ? number_format($this->execution_time, 3) . ' ms' : null;
    }

    /**
     * Get formatted memory usage
     */
    public function getFormattedMemoryUsageAttribute()
    {
        if (!$this->memory_usage) return null;
        
        $bytes = $this->memory_usage;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get sanitized request payload (remove sensitive data)
     */
    public function getSanitizedRequestAttribute()
    {
        $payload = $this->request_payload;
        
        if (is_string($payload)) {
            $data = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Remove sensitive fields
                $sensitiveFields = ['password', 'password_confirmation', 'current_password', 'new_password'];
                foreach ($sensitiveFields as $field) {
                    if (isset($data[$field])) {
                        $data[$field] = '[HIDDEN]';
                    }
                }
                return json_encode($data, JSON_PRETTY_PRINT);
            }
        }
        
        return $payload;
    }

    /**
     * Static method to create transaction log entry
     */
    public static function logTransaction(array $data)
    {
        return self::create(array_merge($data, [
            'transaction_id' => self::generateTransactionId(),
            'created_at' => now(),
        ]));
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId()
    {
        return 'TXN_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8);
    }
}
