<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseProductInventory extends Model
{
    use HasFactory;

    protected $table = 'base_product_inventories';
    protected $primaryKey = 'id_base_inventory';

    protected $fillable = [
        'id_base_product',
        'current_stock',
        'reserved_stock',
        'min_stock',
        'max_stock',
        'average_cost',
        'last_purchase_cost',
        'last_purchase_date',
        'last_stock_check',
        'batch_info',
        'created_by'
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'reserved_stock' => 'decimal:3',
        'min_stock' => 'decimal:3',
        'max_stock' => 'decimal:3',
        'average_cost' => 'decimal:2',
        'last_purchase_cost' => 'decimal:2',
        'last_purchase_date' => 'date',
        'last_stock_check' => 'date',
        'batch_info' => 'array'
    ];

    protected $appends = [
        'available_stock',
        'stock_value',
        'stock_status'
    ];

    // Relationships
    public function baseProduct()
    {
        return $this->belongsTo(BaseProduct::class, 'id_base_product', 'id_base_product');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements()
    {
        return $this->hasMany(BaseProductMovement::class, 'id_base_inventory', 'id_base_inventory');
    }

    // Accessors
    public function getAvailableStockAttribute()
    {
        return max(0, $this->current_stock - $this->reserved_stock);
    }

    public function getStockValueAttribute()
    {
        return $this->current_stock * $this->average_cost;
    }

    public function getStockStatusAttribute()
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        }
        
        if ($this->current_stock <= $this->min_stock) {
            return 'low_stock';
        }
        
        if ($this->max_stock && $this->current_stock >= $this->max_stock) {
            return 'overstock';
        }
        
        return 'in_stock';
    }
}
