<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'asset_code',
        'name',
        'category',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_price',
        'location',
        'condition',
        'status',
        'description',
        'supplier',
        'warranty_until',
        'assigned_to',
        'department',
        'image_url',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_until' => 'date',
        'purchase_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Enum-like constants for validation
    public const CONDITIONS = ['excellent', 'good', 'fair', 'poor', 'damaged'];
    public const STATUSES = ['active', 'inactive', 'maintenance', 'disposed'];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeByCondition($query, $condition)
    {
        return $query->where('condition', $condition);
    }

    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_to');
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    // Accessors
    public function getFormattedPurchasePriceAttribute()
    {
        return $this->purchase_price ? '$' . number_format($this->purchase_price, 2) : null;
    }

    public function getAgeInDaysAttribute()
    {
        return $this->purchase_date ? $this->purchase_date->diffInDays(now()) : null;
    }

    public function getIsUnderWarrantyAttribute()
    {
        return $this->warranty_until && $this->warranty_until->isFuture();
    }

    // Static methods
    public static function getCategories()
    {
        return self::distinct('category')->whereNotNull('category')->pluck('category')->toArray();
    }

    public static function getLocations()
    {
        return self::distinct('location')->whereNotNull('location')->pluck('location')->toArray();
    }

    public static function getDepartments()
    {
        return self::distinct('department')->whereNotNull('department')->pluck('department')->toArray();
    }

    public static function getStatistics()
    {
        return [
            'total_assets' => self::count(),
            'active_assets' => self::active()->count(),
            'maintenance_assets' => self::where('status', 'maintenance')->count(),
            'disposed_assets' => self::where('status', 'disposed')->count(),
            'total_value' => self::sum('purchase_price'),
            'assigned_assets' => self::assigned()->count(),
            'unassigned_assets' => self::unassigned()->count(),
        ];
    }
}
