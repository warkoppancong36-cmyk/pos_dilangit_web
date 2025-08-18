<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    protected $fillable = [
        'user_id',
        'permission_id',
        'type',
        'reason',
        'granted_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the permission
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'permission_id', 'id_permission');
    }

    /**
     * Get the user who granted this permission
     */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Check if permission is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if permission is active (not expired and granted)
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->type === 'grant' && !$this->is_expired;
    }

    /**
     * Scope for active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('type', 'grant')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    /**
     * Scope for expired permissions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Grant permission to user
     */
    public static function grantPermission(int $userId, int $permissionId, int $grantedBy, string $reason = null, $expiresAt = null): self
    {
        return static::updateOrCreate([
            'user_id' => $userId,
            'permission_id' => $permissionId,
        ], [
            'type' => 'grant',
            'reason' => $reason,
            'granted_by' => $grantedBy,
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Deny permission to user
     */
    public static function denyPermission(int $userId, int $permissionId, int $grantedBy, string $reason = null): self
    {
        return static::updateOrCreate([
            'user_id' => $userId,
            'permission_id' => $permissionId,
        ], [
            'type' => 'deny',
            'reason' => $reason,
            'granted_by' => $grantedBy,
            'expires_at' => null,
        ]);
    }

    /**
     * Remove user permission
     */
    public static function removePermission(int $userId, int $permissionId): bool
    {
        return static::where('user_id', $userId)
                    ->where('permission_id', $permissionId)
                    ->delete();
    }
}
