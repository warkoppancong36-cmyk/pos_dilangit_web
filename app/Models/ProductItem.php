<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductItem extends Model
{
    use HasFactory;

    protected $table = 'product_items';
    protected $primaryKey = 'id_product_item';

    protected $fillable = [
        'product_id',
        'item_id',
        'quantity_needed',
        'unit', // Unit for this specific relationship (could be different from item's base unit)
        'cost_per_unit',
        'is_critical',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity_needed' => 'decimal:3', // Support 3 decimal places to match database schema
        'cost_per_unit' => 'decimal:2',
        'is_critical' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'unit', // Include unit from item relationship in JSON
        'total_cost_per_product',
        'formatted_cost_per_unit',
        'formatted_total_cost',
    ];

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id_product');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id_item');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Computed Attributes
     */
    public function getUnitAttribute(): string
    {
        // Priority: unit stored in product_items table, then fallback to item's unit
        return $this->attributes['unit'] ?? $this->item?->unit ?? 'pcs';
    }

    public function getTotalCostPerProductAttribute(): float
    {
        $cost = $this->cost_per_unit ?? $this->item->cost_per_unit ?? 0;
        return $this->quantity_needed * $cost;
    }

    public function getFormattedCostPerUnitAttribute(): string
    {
        $cost = $this->cost_per_unit ?? $this->item->cost_per_unit ?? 0;
        return 'Rp ' . number_format($cost, 0, ',', '.');
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return 'Rp ' . number_format($this->total_cost_per_product, 0, ',', '.');
    }

    /**
     * Methods
     */
    public function canProduce(int $quantity = 1): bool
    {
        $requiredQuantity = $this->quantity_needed * $quantity;
        return $this->item->current_stock >= $requiredQuantity;
    }

    public function getRequiredQuantityFor(int $productQuantity): float
    {
        return $this->quantity_needed * $productQuantity;
    }

    public function getAvailableProductQuantity(): int
    {
        if ($this->quantity_needed <= 0) {
            return 0;
        }
        return (int) floor($this->item->current_stock / $this->quantity_needed);
    }
}
