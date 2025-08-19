<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariantItem extends Model
{
    use HasFactory;

    protected $table = 'variant_items';
    protected $primaryKey = 'id_variant_item';

    protected $fillable = [
        'id_variant',
        'id_item',
        'quantity_needed',
        'unit',
        'cost_per_unit',
        'is_critical',
        'notes',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity_needed' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'is_critical' => 'boolean',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_cost',
        'total_cost',
        'stock_status',
    ];

    /**
     * Relationship dengan Variant
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'id_variant', 'id_variant');
    }

    /**
     * Relationship dengan Item
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'id_item', 'id_item')
                    ->with(['inventory']);
    }

    /**
     * Relationship dengan User Creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship dengan User Updater
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor untuk formatted cost
     */
    public function getFormattedCostAttribute(): string
    {
        return 'Rp ' . number_format($this->cost_per_unit ?? 0, 0, ',', '.');
    }

    /**
     * Accessor untuk total cost
     */
    public function getTotalCostAttribute(): float
    {
        return ($this->cost_per_unit ?? 0) * ($this->quantity_needed ?? 0);
    }

    /**
     * Accessor untuk stock status
     */
    public function getStockStatusAttribute(): array
    {
        if (!$this->item || !$this->item->inventory) {
            return [
                'status' => 'unknown',
                'color' => 'grey',
                'text' => 'Tidak diketahui'
            ];
        }

        $currentStock = $this->item->inventory->current_stock ?? 0;
        $needed = $this->quantity_needed ?? 0;

        if ($currentStock <= 0) {
            return [
                'status' => 'out',
                'color' => 'error',
                'text' => 'Stok habis'
            ];
        }

        if ($currentStock < $needed) {
            return [
                'status' => 'low',
                'color' => 'warning',
                'text' => 'Stok kurang'
            ];
        }

        return [
            'status' => 'sufficient',
            'color' => 'success',
            'text' => 'Stok cukup'
        ];
    }

    /**
     * Scope untuk active variant items
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope untuk critical items
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope untuk filter berdasarkan variant
     */
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('id_variant', $variantId);
    }

    /**
     * Scope untuk filter berdasarkan item
     */
    public function scopeByItem($query, $itemId)
    {
        return $query->where('id_item', $itemId);
    }
}
