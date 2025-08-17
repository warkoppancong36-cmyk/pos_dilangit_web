<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'items';
    protected $primaryKey = 'id_item';

    protected $fillable = [
        'item_code',
        'name',
        'description',
        'unit',
        'cost_per_unit',
        'storage_location',
        'expiry_date',
        'active',
        'is_delivery',
        'is_takeaway',
        'properties',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cost_per_unit' => 'decimal:2',
        'expiry_date' => 'date',
        'active' => 'boolean',
        'is_delivery' => 'boolean',
        'is_takeaway' => 'boolean',
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_cost_per_unit',
        'stock_status',
        'is_low_stock',
        'stock_percentage',
    ];

    /**
     * Boot method untuk auto-generate item code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->item_code)) {
                $item->item_code = $item->generateItemCode();
            }
        });
    }

    /**
     * Generate unique item code
     */
    public function generateItemCode(): string
    {
        $prefix = 'ITM';
        $date = now()->format('ymd');
        
        $attempts = 0;
        $maxAttempts = 100;
        
        do {
            $lastItem = static::withTrashed()  // Include soft deleted items
                ->where('item_code', 'like', $prefix . $date . '%')
                ->orderBy('item_code', 'desc')
                ->first();

            if ($lastItem) {
                $lastNumber = (int) substr($lastItem->item_code, -4);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $itemCode = $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            
            // Check if this code already exists
            $exists = static::withTrashed()
                ->where('item_code', $itemCode)
                ->exists();
                
            if (!$exists) {
                return $itemCode;
            }
            
            $attempts++;
            
            // If we can't find a unique code, add microseconds to make it unique
            if ($attempts >= $maxAttempts) {
                $microtime = substr(microtime(true) * 1000, -4);
                return $prefix . $date . $microtime;
            }
            
        } while ($exists && $attempts < $maxAttempts);

        // Fallback - should never reach here
        return $prefix . $date . rand(1000, 9999);
    }

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_items', 'item_id', 'product_id', 'id_item', 'id_product')
            ->withPivot(['quantity_needed', 'unit', 'cost_per_unit', 'is_critical', 'notes'])
            ->withTimestamps();
    }

    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class, 'item_id', 'id_item');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'item_id', 'id_item');
    }

    /**
     * Get inventory record for this item (for stock tracking)
     */
    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'id_item', 'id_item');
    }

    /**
     * Get current stock from inventory table
     */
    public function getCurrentStockAttribute()
    {
        return $this->inventory ? $this->inventory->current_stock : 0;
    }

    /**
     * Get minimum stock from inventory table
     */
    public function getMinimumStockAttribute()
    {
        return $this->inventory ? $this->inventory->reorder_level : 0;
    }

    /**
     * Get maximum stock from inventory table
     */
    public function getMaximumStockAttribute()
    {
        return $this->inventory ? $this->inventory->max_stock_level : 0;
    }

    /**
     * Computed Attributes
     */
    public function getFormattedCostPerUnitAttribute(): string
    {
        return 'Rp ' . number_format($this->cost_per_unit, 0, ',', '.');
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->current_stock <= $this->minimum_stock) {
            return 'low_stock';
        } elseif ($this->maximum_stock && $this->current_stock >= $this->maximum_stock) {
            return 'overstock';
        } else {
            return 'in_stock';
        }
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->minimum_stock;
    }

    public function getStockPercentageAttribute(): float
    {
        if (!$this->maximum_stock || $this->maximum_stock <= 0) {
            return 0;
        }
        return min(100, ($this->current_stock / $this->maximum_stock) * 100);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    public function scopeLowStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->whereRaw('current_stock <= reorder_level');
        });
    }

    public function scopeOutOfStock($query)
    {
        return $query->whereHas('inventory', function ($q) {
            $q->where('current_stock', '<=', 0);
        });
    }

    public function scopeByUnit($query, $unit)
    {
        return $query->where('unit', $unit);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('item_code', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    /**
     * Methods
     */
    public function addStock(float $quantity, string $reason = null, $purchaseItemId = null): bool
    {
        $this->current_stock += $quantity;
        $result = $this->save();

        return $result;
    }

    public function reduceStock(float $quantity, string $reason = null): bool
    {
        if ($this->current_stock < $quantity) {
            return false;
        }

        $this->current_stock -= $quantity;
        $result = $this->save();

        return $result;
    }

    public function updateStock(float $newStock, string $reason = null): bool
    {
        $oldStock = $this->current_stock;
        $this->current_stock = $newStock;
        $result = $this->save();

        return $result;
    }

    public function calculateTotalValue(): float
    {
        return $this->current_stock * $this->cost_per_unit;
    }
}
