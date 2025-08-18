<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'permissions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get users with this role
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get permissions assigned to this role (many-to-many)
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id', 'id', 'id_permission')
                    ->withTimestamps();
    }

    /**
     * Check if role has specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Check in new permission system first
        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        // Fallback to old JSON permissions for backward compatibility
        if (!$this->permissions) {
            return false;
        }

        return in_array($permission, $this->permissions);
    }

    /**
     * Get all permission names for this role
     */
    public function getPermissionNames(): array
    {
        $newPermissions = $this->permissions()->where('is_active', true)->pluck('name')->toArray();
        $oldPermissions = $this->permissions ?? [];
        
        return array_unique(array_merge($newPermissions, $oldPermissions));
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(string|int $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        } else {
            $permission = Permission::find($permission);
        }

        if ($permission && !$this->permissions()->where('permission_id', $permission->id_permission)->exists()) {
            $this->permissions()->attach($permission->id_permission);
        }
    }

    /**
     * Remove permission from role
     */
    public function removePermission(string|int $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->first();
        } else {
            $permission = Permission::find($permission);
        }

        if ($permission) {
            $this->permissions()->detach($permission->id_permission);
        }
    }

    /**
     * Sync permissions with role
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = [];
        
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $perm = Permission::where('name', $permission)->first();
                if ($perm) {
                    $permissionIds[] = $perm->id_permission;
                }
            } else {
                $permissionIds[] = $permission;
            }
        }

        $this->permissions()->sync($permissionIds);
    }

    /**
     * Get role by name
     */
    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }

    /**
     * Check if role is system role
     */
    public function getIsSystemAttribute(): bool
    {
        return in_array($this->name, ['admin', 'super_admin']);
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
