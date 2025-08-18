<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    protected $primaryKey = 'id_permission';
    
    protected $fillable = [
        'name',
        'display_name',
        'module',
        'action',
        'description',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get roles that have this permission
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id', 'id_permission', 'id')
                    ->withTimestamps();
    }

    /**
     * Get users that have this permission directly
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permissions', 'permission_id', 'user_id')
                    ->withPivot(['type', 'reason', 'granted_by', 'expires_at'])
                    ->withTimestamps();
    }

    /**
     * Get user permissions
     */
    public function userPermissions(): HasMany
    {
        return $this->hasMany(UserPermission::class, 'permission_id');
    }

    /**
     * Get permissions by module
     */
    public static function byModule(string $module)
    {
        return static::where('module', $module)->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Get all modules
     */
    public static function getModules(): array
    {
        return static::where('is_active', true)
                    ->distinct()
                    ->pluck('module')
                    ->sort()
                    ->values()
                    ->toArray();
    }

    /**
     * Create permission with format: module_action
     */
    public static function createPermission(string $module, string $action, string $displayName = null): self
    {
        $name = strtolower($module . '_' . $action);
        $displayName = $displayName ?? ucfirst($action) . ' ' . ucfirst($module);

        return static::create([
            'name' => $name,
            'display_name' => $displayName,
            'module' => strtolower($module),
            'action' => strtolower($action),
            'is_active' => true,
        ]);
    }

    /**
     * Check if permission exists
     */
    public static function exists(string $name): bool
    {
        return static::where('name', $name)->exists();
    }

    /**
     * Get permission by name
     */
    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }

    /**
     * Get system permissions
     */
    public static function systemPermissions()
    {
        return static::where('is_system', true)->where('is_active', true);
    }

    /**
     * Get grouped permissions by module
     */
    public static function groupedByModule()
    {
        return static::where('is_active', true)
                    ->orderBy('module')
                    ->orderBy('sort_order')
                    ->get()
                    ->groupBy('module');
    }
}
