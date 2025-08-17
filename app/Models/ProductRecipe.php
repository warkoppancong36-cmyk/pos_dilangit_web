<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'description',
        'total_cost',
        'portion_size',
        'portion_unit',
        'preparation_time',
        'difficulty_level',
        'instructions',
        'active',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'active' => 'boolean',
        'instructions' => 'array',
        'portion_size' => 'integer',
        'preparation_time' => 'integer',
    ];

    /**
     * Get the product that owns the recipe
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the recipe items for this recipe
     */
    public function recipeItems(): HasMany
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    /**
     * Get the recipe items with item details
     */
    public function itemsWithDetails()
    {
        return $this->recipeItems()->with('item');
    }

    /**
     * Calculate the total cost of the recipe
     */
    public function calculateTotalCost(): float
    {
        $totalCost = $this->recipeItems()->with('item')->get()->sum(function ($recipeItem) {
            return $recipeItem->quantity * ($recipeItem->item->cost_per_unit ?? 0);
        });

        return (float) $totalCost;
    }

    /**
     * Update the total cost based on current item prices
     */
    public function updateTotalCost(): void
    {
        $this->update(['total_cost' => $this->calculateTotalCost()]);
    }

    /**
     * Scope to get active recipes only
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get recipes for a specific product
     */
    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }
}
