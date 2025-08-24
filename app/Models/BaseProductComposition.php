<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseProductComposition extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'base_product_compositions';
    protected $primaryKey = 'id';

    protected $fillable = [
        'base_product_id',
        'ingredient_base_product_id',
        'quantity',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'is_active' => 'boolean'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Get the base product that this composition belongs to
     */
    public function baseProduct(): BelongsTo
    {
        return $this->belongsTo(BaseProduct::class, 'base_product_id', 'id_base_product');
    }

    /**
     * Get the ingredient base product used in this composition
     */
    public function ingredientBaseProduct(): BelongsTo
    {
        return $this->belongsTo(BaseProduct::class, 'ingredient_base_product_id', 'id_base_product');
    }

    /**
     * Calculate the total cost of this composition
     */
    public function getTotalCostAttribute(): float
    {
        if (!$this->ingredientBaseProduct) {
            return 0;
        }
        
        return $this->quantity * $this->ingredientBaseProduct->cost_per_unit;
    }

    /**
     * Get formatted total cost
     */
    public function getFormattedTotalCostAttribute(): string
    {
        return 'Rp ' . number_format($this->total_cost, 0, ',', '.');
    }

    /**
     * Calculate how many portions can be made with current stock
     */
    public function getAvailablePortionsAttribute(): int
    {
        if (!$this->ingredientBaseProduct || $this->quantity <= 0) {
            return 0;
        }
        
        return floor($this->ingredientBaseProduct->current_stock / $this->quantity);
    }

    /**
     * Check if this composition can be produced
     */
    public function getCanProduceAttribute(): bool
    {
        return $this->available_portions > 0;
    }

    /**
     * Scope to filter by active status
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by base product
     */
    public function scopeForBaseProduct($query, $baseProductId)
    {
        return $query->where('base_product_id', $baseProductId);
    }

    /**
     * Scope to filter by ingredient
     */
    public function scopeWithIngredient($query, $ingredientId)
    {
        return $query->where('ingredient_base_product_id', $ingredientId);
    }
}
