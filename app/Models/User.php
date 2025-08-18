<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'password',
        'role_id',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'last_login_device',
        'login_attempts',
        'locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    /**
     * Get the role that belongs to the user
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the login logs for the user
     */
    public function loginLogs(): HasMany
    {
        return $this->hasMany(UserLoginLog::class);
    }

    /**
     * Get direct permissions assigned to this user
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
                    ->withPivot(['type', 'reason', 'granted_by', 'expires_at'])
                    ->withTimestamps();
    }

    /**
     * Get user permissions
     */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Get uploaded assets
     */
    public function uploadedAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'uploaded_by');
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Check direct user permissions first
        $userPermission = $this->userPermissions()
                              ->whereHas('permission', function ($q) use ($permission) {
                                  $q->where('name', $permission);
                              })
                              ->active()
                              ->first();

        if ($userPermission) {
            return $userPermission->type === 'grant';
        }

        // Check role permissions
        return $this->role && $this->role->hasPermission($permission);
    }

    /**
     * Get all permissions for this user (role + direct permissions)
     */
    public function getAllPermissions(): array
    {
        $rolePermissions = $this->role ? $this->role->getPermissionNames() : [];
        
        $directPermissions = $this->userPermissions()
                                 ->active()
                                 ->with('permission')
                                 ->get()
                                 ->filter(function ($up) {
                                     return $up->type === 'grant';
                                 })
                                 ->pluck('permission.name')
                                 ->toArray();

        $deniedPermissions = $this->userPermissions()
                                 ->where('type', 'deny')
                                 ->with('permission')
                                 ->get()
                                 ->pluck('permission.name')
                                 ->toArray();

        $allPermissions = array_unique(array_merge($rolePermissions, $directPermissions));
        
        // Remove denied permissions
        return array_diff($allPermissions, $deniedPermissions);
    }

    /**
     * Grant permission to user
     */
    public function grantPermission(string $permission, int $grantedBy, string $reason = null, $expiresAt = null): bool
    {
        $permissionModel = Permission::where('name', $permission)->first();
        
        if (!$permissionModel) {
            return false;
        }

        UserPermission::grantPermission(
            $this->id,
            $permissionModel->id_permission,
            $grantedBy,
            $reason,
            $expiresAt
        );

        return true;
    }

    /**
     * Deny permission to user
     */
    public function denyPermission(string $permission, int $grantedBy, string $reason = null): bool
    {
        $permissionModel = Permission::where('name', $permission)->first();
        
        if (!$permissionModel) {
            return false;
        }

        UserPermission::denyPermission(
            $this->id,
            $permissionModel->id_permission,
            $grantedBy,
            $reason
        );

        return true;
    }

    /**
     * Remove permission from user
     */
    public function removePermission(string $permission): bool
    {
        $permissionModel = Permission::where('name', $permission)->first();
        
        if (!$permissionModel) {
            return false;
        }

        return UserPermission::removePermission($this->id, $permissionModel->id_permission);
    }

    /**
     * Check if user has specific permission (backward compatibility)
     */
    public function hasPermissionOld(string $permission): bool
    {
        return $this->role && $this->role->hasPermission($permission);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is manager
     */
    public function isManager(): bool
    {
        return $this->hasRole('manager');
    }

    /**
     * Check if user is kasir
     */
    public function isKasir(): bool
    {
        return $this->hasRole('kasir');
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for users with specific role
     */
    public function scopeWithRole($query, string $roleName)
    {
        return $query->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }

    /**
     * Check if user is locked
     */
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * Lock user account
     */
    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }

    /**
     * Unlock user account
     */
    public function unlockAccount(): void
    {
        $this->update([
            'locked_until' => null,
            'login_attempts' => 0,
        ]);
    }

    /**
     * Increment login attempts
     */
    public function incrementLoginAttempts(): void
    {
        $this->increment('login_attempts');
    }

    /**
     * Reset login attempts
     */
    public function resetLoginAttempts(): void
    {
        $this->update(['login_attempts' => 0]);
    }
}
