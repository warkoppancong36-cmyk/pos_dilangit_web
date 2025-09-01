<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BluetoothDevice extends Model
{
    use HasFactory;

    protected $table = 'bluetooth_devices';
    protected $primaryKey = 'id_bluetooth_device';

    protected $fillable = [
        'id_user',
        'device_name',
        'device_address',
        'device_type',
        'manufacturer',
        'model',
        'device_capabilities',
        'connection_settings',
        'is_default',
        'is_active',
        'last_connected_at',
        'notes'
    ];

    protected $casts = [
        'device_capabilities' => 'array',
        'connection_settings' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'last_connected_at' => 'datetime'
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for devices by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('device_type', $type);
    }

    /**
     * Scope for default devices
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for user devices
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('id_user', $userId);
    }

    /**
     * Get device type label
     */
    public function getDeviceTypeLabelAttribute()
    {
        $labels = [
            'printer' => 'Printer',
            'scanner' => 'Scanner',
            'cash_drawer' => 'Cash Drawer',
            'scale' => 'Scale',
            'other' => 'Other'
        ];

        return $labels[$this->device_type] ?? 'Unknown';
    }

    /**
     * Get connection status
     */
    public function getConnectionStatusAttribute()
    {
        if (!$this->is_active) {
            return 'disabled';
        }

        if (!$this->last_connected_at) {
            return 'never_connected';
        }

        // Consider connected if last connection was within 1 hour
        $oneHourAgo = now()->subHour();
        return $this->last_connected_at->gt($oneHourAgo) ? 'connected' : 'disconnected';
    }

    /**
     * Set as default device for this type and user
     */
    public function setAsDefault()
    {
        // Remove default from other devices of same type for this user
        static::where('id_user', $this->id_user)
            ->where('device_type', $this->device_type)
            ->where('id_bluetooth_device', '!=', $this->id_bluetooth_device)
            ->update(['is_default' => false]);

        // Set this device as default
        $this->update(['is_default' => true]);
    }

    /**
     * Update last connected timestamp
     */
    public function updateLastConnected()
    {
        $this->update(['last_connected_at' => now()]);
    }
}
