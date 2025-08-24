<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductComposition extends Model
{
    use HasFactory;

    protected $table = 'product_compositions';
    protected $primaryKey = 'id_composition';

    protected $fillable = [
        'id_product',
        'id_base_product',
        'quantity_needed',
        'unit',
        'cost_per_unit',
        'is_essential',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'quantity_needed' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'is_essential' => 'boolean'
    ];

    protected $appends = [
        'total_cost',
        'formatted_quantity',
        'formatted_cost'
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function baseProduct()
    {
        return $this->belongsTo(BaseProduct::class, 'id_base_product', 'id_base_product');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getTotalCostAttribute()
    {
        return $this->quantity_needed * $this->cost_per_unit;
    }

    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity_needed, 3) . ' ' . $this->unit;
    }

    public function getFormattedCostAttribute()
    {
        return 'Rp ' . number_format($this->total_cost, 0, ',', '.');
    }

    // Methods
    public function checkAvailability($productionQuantity = 1)
    {
        $requiredQuantity = $this->quantity_needed * $productionQuantity;
        $baseProduct = $this->baseProduct;
        $inventory = $baseProduct->inventory;

        if (!$inventory) {
            return [
                'available' => false,
                'current_stock' => 0,
                'required_quantity' => $requiredQuantity,
                'shortage' => $requiredQuantity
            ];
        }

        $available = $inventory->available_stock >= $requiredQuantity;
        $shortage = $available ? 0 : $requiredQuantity - $inventory->available_stock;

        return [
            'available' => $available,
            'current_stock' => $inventory->available_stock,
            'required_quantity' => $requiredQuantity,
            'shortage' => $shortage
        ];
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
            
            // Set cost from base product if not provided
            if (!$model->cost_per_unit && $model->baseProduct) {
                $model->cost_per_unit = $model->baseProduct->cost_per_unit;
            }
        });
    }
}
