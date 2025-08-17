<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';
    protected $primaryKey = 'id_inventory';

    protected $fillable = [
        'id_product',
        'id_variant',
        'id_item',
        'current_stock',
        'reserved_stock',
        'reorder_level',
        'max_stock_level',
        'average_cost',
        'last_restocked',
        'last_counted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'reserved_stock' => 'integer',
        'reorder_level' => 'integer',
        'max_stock_level' => 'integer',
        'average_cost' => 'decimal:2',
        'last_restocked' => 'datetime',
        'last_counted' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'available_stock',
        'is_low_stock',
        'stock_status'
    ];

    /**
     * Get the available stock (computed field)
     */
    public function getAvailableStockAttribute(): int
    {
        return $this->current_stock - $this->reserved_stock;
    }

    /**
     * Check if stock is low
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    /**
     * Get stock status
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->current_stock <= $this->reorder_level) {
            return 'low_stock';
        } elseif ($this->max_stock_level && $this->current_stock >= $this->max_stock_level) {
            return 'overstock';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    // DISABLED - Variant system removed
    // public function variant(): BelongsTo
    // {
    //     return $this->belongsTo(Variant::class, 'id_variant', 'id_variant');
    // }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'id_item', 'id_item');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class, 'id_inventory', 'id_inventory');
    }

    /**
     * Scopes
     */
    public function scopeLowStock($query, $threshold = null)
    {
        if ($threshold) {
            return $query->whereRaw('current_stock <= ?', [$threshold]);
        }
        return $query->whereRaw('current_stock <= reorder_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }

    public function scopeInStock($query)
    {
        return $query->where('current_stock', '>', 0);
    }

    public function scopeOverstock($query)
    {
        return $query->whereRaw('current_stock >= max_stock_level AND max_stock_level IS NOT NULL');
    }

    /**
     * Methods
     */
    public function addStock(int $quantity): bool
    {
        $this->current_stock += $quantity;
        $this->last_restocked = now();
        return $this->save();
    }

    public function removeStock(int $quantity): bool
    {
        if ($this->current_stock >= $quantity) {
            $this->current_stock -= $quantity;
            return $this->save();
        }
        return false;
    }

    public function reserveStock(int $quantity): bool
    {
        if ($this->available_stock >= $quantity) {
            $this->reserved_stock += $quantity;
            return $this->save();
        }
        return false;
    }

    public function releaseReservedStock(int $quantity): bool
    {
        if ($this->reserved_stock >= $quantity) {
            $this->reserved_stock -= $quantity;
            return $this->save();
        }
        return false;
    }

    public function updateAverageCost(float $newCost, int $quantity): bool
    {
        if ($this->current_stock > 0) {
            $totalValue = ($this->average_cost * $this->current_stock) + ($newCost * $quantity);
            $totalQuantity = $this->current_stock + $quantity;
            $this->average_cost = $totalValue / $totalQuantity;
        } else {
            $this->average_cost = $newCost;
        }
        return $this->save();
    }
}
