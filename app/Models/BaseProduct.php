<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class BaseProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'base_products';
    protected $primaryKey = 'id_base_product';

    protected $fillable = [
        'name',
        'sku',
        'description',
        'category_id',
        'unit',
        'cost_per_unit',
        'selling_price',
        'min_stock',
        'max_stock',
        'is_active',
        'is_perishable',
        'shelf_life_days',
        'storage_type',
        'supplier_info',
        'notes',
        'image_url',
        'nutritional_info',
        'allergen_info',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_perishable' => 'boolean',
        'cost_per_unit' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'nutritional_info' => 'array',
        'allergen_info' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $appends = [
        'profit_margin',
        'current_stock',
        'stock_status',
        'formatted_cost',
        'formatted_price'
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id_category');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function inventory()
    {
        return $this->hasOne(BaseProductInventory::class, 'id_base_product', 'id_base_product');
    }

    public function productCompositions()
    {
        return $this->hasMany(ProductComposition::class, 'id_base_product', 'id_base_product');
    }

    public function movements()
    {
        return $this->hasManyThrough(
            BaseProductMovement::class,
            BaseProductInventory::class,
            'id_base_product',
            'id_base_inventory',
            'id_base_product',
            'id_base_inventory'
        );
    }

    // Scopes
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeSearch(Builder $query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeLowStock(Builder $query)
    {
        return $query->whereHas('inventory', function($q) {
            $q->whereRaw('current_stock <= min_stock');
        });
    }

    public function scopePerishable(Builder $query)
    {
        return $query->where('is_perishable', true);
    }

    // Accessors
    public function getProfitMarginAttribute()
    {
        if ($this->selling_price <= 0 || $this->cost_per_unit <= 0) {
            return 0;
        }
        return round((($this->selling_price - $this->cost_per_unit) / $this->selling_price) * 100, 2);
    }

    public function getCurrentStockAttribute()
    {
        return $this->inventory ? $this->inventory->current_stock : 0;
    }

    public function getStockStatusAttribute()
    {
        $currentStock = $this->current_stock;
        
        if ($currentStock <= 0) {
            return 'out_of_stock';
        }
        
        if ($currentStock <= $this->min_stock) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    public function getFormattedCostAttribute()
    {
        return 'Rp ' . number_format($this->cost_per_unit, 0, ',', '.');
    }

    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->selling_price, 0, ',', '.');
    }

    // Methods
    public function updateStock($newStock, $userId = null, $notes = null)
    {
        if (!$this->inventory) {
            BaseProductInventory::create([
                'id_base_product' => $this->id_base_product,
                'current_stock' => $newStock,
                'min_stock' => $this->min_stock,
                'max_stock' => $this->max_stock,
                'average_cost' => $this->cost_per_unit,
                'last_purchase_date' => now(),
                'created_by' => $userId
            ]);
        } else {
            $oldStock = $this->inventory->current_stock;
            $this->inventory->update(['current_stock' => $newStock]);
            
            // Create movement record
            BaseProductMovement::create([
                'id_base_inventory' => $this->inventory->id_base_inventory,
                'movement_type' => $newStock > $oldStock ? 'in' : 'out',
                'quantity' => abs($newStock - $oldStock),
                'stock_before' => $oldStock,
                'stock_after' => $newStock,
                'unit_cost' => $this->cost_per_unit,
                'total_cost' => $this->cost_per_unit * abs($newStock - $oldStock),
                'reference_type' => 'manual_adjustment',
                'notes' => $notes ?? 'Manual stock adjustment',
                'movement_date' => now(),
                'created_by' => $userId
            ]);
        }
    }

    public function generateSku()
    {
        if ($this->sku) {
            return $this->sku;
        }

        $prefix = 'BP'; // Base Product
        $categoryCode = $this->category ? strtoupper(substr($this->category->name, 0, 3)) : 'GEN';
        $dateCode = date('dmy');
        $randomCode = strtoupper(substr(uniqid(), -4));
        
        $sku = $prefix . $categoryCode . $dateCode . $randomCode;
        
        // Ensure uniqueness
        $counter = 1;
        $originalSku = $sku;
        while (self::where('sku', $sku)->exists()) {
            $sku = $originalSku . $counter;
            $counter++;
        }
        
        $this->update(['sku' => $sku]);
        return $sku;
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        static::created(function ($model) {
            // Auto generate SKU if not provided
            if (!$model->sku) {
                $model->generateSku();
            }
            
            // Create initial inventory record
            BaseProductInventory::create([
                'id_base_product' => $model->id_base_product,
                'current_stock' => 0,
                'min_stock' => $model->min_stock,
                'max_stock' => $model->max_stock,
                'average_cost' => $model->cost_per_unit,
                'last_purchase_date' => now(),
                'created_by' => $model->created_by
            ]);
        });
    }
}
