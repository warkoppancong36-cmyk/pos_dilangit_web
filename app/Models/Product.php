<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';
    protected $primaryKey = 'id_product';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sku',
        'barcode',
        'price',
        'cost',
        'markup_percentage',
        'weight',
        'dimensions',
        'image',
        'category_id',
        'brand',
        'tags',
        'meta_title',
        'meta_description',
        'status',
        'active',
        'featured',
        'available_in_kitchen',
        'available_in_bar',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'markup_percentage' => 'decimal:2',
        'weight' => 'decimal:2',
        'active' => 'boolean',
        'featured' => 'boolean',
        'available_in_kitchen' => 'boolean',
        'available_in_bar' => 'boolean',
        'tags' => 'array',
        'dimensions' => 'array',
    ];

    protected $appends = [
        'image_url',
        'formatted_price',
        'formatted_cost',
    ];

    protected $attributes = [
        'status' => 'draft',
        'active' => true,
        'featured' => false,
        'available_in_kitchen' => true,
        'available_in_bar' => true,
    ];

    // Boot method untuk auto-generate slug dan SKU
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            
            if (empty($product->sku)) {
                $product->sku = $product->generateSku();
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Relasi ke Category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id_category');
    }

    /**
     * Relasi ke User yang membuat produk
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    /**
     * Relasi ke User yang terakhir mengupdate produk
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    /**
     * Relasi ke User yang menghapus produk
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    /**
     * DISABLED - Variant system removed
     */
    // public function variants(): HasMany
    // {
    //     return $this->hasMany(Variant::class, 'id_product', 'id_product');
    // }

    /**
     * Relasi ke Inventory
     */
    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class, 'id_product', 'id_product');
    }

    /**
     * Relasi ke TransactionLog
     */
    public function transactionLogs(): HasMany
    {
        return $this->hasMany(TransactionLog::class, 'related_id', 'id_product')
                    ->where('related_type', 'product');
    }

    /**
     * Get the recipes for this product
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'product_id', 'id_product');
    }

    /**
     * Get active recipes for this product
     */
    public function activeRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'product_id', 'id_product')->where('active', true);
    }

    /**
     * Get the items that make up this product
     */
    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class, 'product_id', 'id_product');
    }

    /**
     * Get items with details through pivot table
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'product_items', 'product_id', 'item_id', 'id_product', 'id_item')
            ->withPivot(['id_product_item', 'quantity_needed', 'cost_per_unit', 'is_critical', 'notes'])
            ->withTimestamps();
    }

    /**
     * Scope untuk produk aktif
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope untuk produk tidak aktif
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Scope untuk produk unggulan
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope untuk produk berdasarkan status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope untuk produk dengan stok rendah
     */
    public function scopeLowStock($query)
    {
        // Produk dengan inventory yang stoknya rendah
        // ATAU produk tanpa inventory dianggap stok rendah jika tidak aktif
        return $query->where(function ($q) {
            $q->whereHas('inventory', function ($subQ) {
                $subQ->whereColumn('current_stock', '<=', 'reorder_level')
                     ->where('current_stock', '>', 0);
            });
        });
    }

    /**
     * Scope untuk produk dengan stok tersedia
     */
    public function scopeInStock($query)
    {
        // Produk dengan inventory yang ada stoknya
        // ATAU produk tanpa inventory tetapi aktif (dianggap selalu tersedia)
        return $query->where(function ($q) {
            $q->whereHas('inventory', function ($subQ) {
                $subQ->where('current_stock', '>', 0);
            })
            ->orWhereDoesntHave('inventory');  // Produk tanpa inventory dianggap tersedia
        });
    }

    /**
     * Scope untuk produk dengan stok habis
     */
    public function scopeOutOfStock($query)
    {
        // Hanya produk yang punya inventory DAN stoknya habis
        return $query->whereHas('inventory', function ($q) {
            $q->where('current_stock', '<=', 0);
        });
    }

    /**
     * Scope untuk pencarian produk
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope untuk filter berdasarkan brand
     */
    public function scopeByBrand($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope untuk filter berdasarkan range harga
     */
    public function scopePriceRange($query, $minPrice = null, $maxPrice = null)
    {
        if ($minPrice !== null) {
            $query->where('price', '>=', $minPrice);
        }
        
        if ($maxPrice !== null) {
            $query->where('price', '<=', $maxPrice);
        }
        
        return $query;
    }

    /**
     * Generate SKU otomatis
     */
    public function generateSku(): string
    {
        $prefix = 'PRD';
        $categoryCode = $this->category ? strtoupper(substr($this->category->name, 0, 3)) : 'GEN';
        $timestamp = now()->format('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$categoryCode}-{$timestamp}-{$random}";
    }

    /**
     * Calculate HPP (Harga Pokok Produksi) from all product items
     * This method calculates the total cost based on all items needed for this product
     */
    public function calculateHPP(): float
    {
        $totalCost = 0;
        
        // Get all items with their requirements for this product
        $productItems = $this->productItems()->with('item')->get();
        
        foreach ($productItems as $productItem) {
            // Use cost_per_unit from ProductItem if specified, otherwise use from Item
            $itemCost = $productItem->cost_per_unit ?? $productItem->item->cost_per_unit ?? 0;
            
            // Calculate total cost for this item (quantity needed × cost per unit)
            $itemTotalCost = $productItem->quantity_needed * $itemCost;
            $totalCost += $itemTotalCost;
        }
        
        return round($totalCost, 2);
    }

    /**
     * Calculate HPP based on latest purchase prices
     */
    public function calculateHPPFromLatestPurchases(): float
    {
        $totalCost = 0;
        
        $productItems = $this->productItems()->with(['item'])->get();
        
        foreach ($productItems as $productItem) {
            // Use same logic as getHPPBreakdown('latest')
            $itemCost = $this->getLatestPurchaseCost($productItem) ?? $productItem->item->cost_per_unit ?? 0;
            
            $itemTotalCost = $productItem->quantity_needed * $itemCost;
            $totalCost += $itemTotalCost;
        }
        
        return round($totalCost, 2);
    }

    /**
     * Calculate HPP based on average purchase prices
     */
    public function calculateHPPFromAveragePurchases(): float
    {
        $totalCost = 0;
        
        $productItems = $this->productItems()->with(['item'])->get();
        
        foreach ($productItems as $productItem) {
            // Use same logic as getHPPBreakdown('average')
            $itemCost = $this->getAveragePurchaseCost($productItem) ?? $productItem->item->cost_per_unit ?? 0;
            
            $itemTotalCost = $productItem->quantity_needed * $itemCost;
            $totalCost += $itemTotalCost;
        }
        
        return round($totalCost, 2);
    }

    /**
     * Update product cost with calculated HPP
     */
    public function updateCostFromHPP(string $method = 'current'): bool
    {
        $hpp = match($method) {
            'latest' => $this->calculateHPPFromLatestPurchases(),
            'average' => $this->calculateHPPFromAveragePurchases(),
            default => $this->calculateHPP(),
        };
        
        return $this->update(['cost' => $hpp]);
    }

    /**
     * Get breakdown of HPP calculation
     */
    public function getHPPBreakdown(string $method = 'current'): array
    {
        $breakdown = [];
        $totalCost = 0;
        
        $productItems = $this->productItems()->with(['item'])->get();
        
        foreach ($productItems as $productItem) {
            // Get cost based on method - since purchase_items relates to products, not items directly
            // We need to get purchase costs from product purchases or use item's cost_per_unit
            $itemCost = match($method) {
                'latest' => $this->getLatestPurchaseCost($productItem) ?? $productItem->item->cost_per_unit ?? 0,
                'average' => $this->getAveragePurchaseCost($productItem) ?? $productItem->item->cost_per_unit ?? 0,
                default => $productItem->cost_per_unit ?? $productItem->item->cost_per_unit ?? 0,
            };
            
            $itemTotalCost = $productItem->quantity_needed * $itemCost;
            $totalCost += $itemTotalCost;
            
            $breakdown[] = [
                'item_name' => $productItem->item->name,
                'item_code' => $productItem->item->item_code,
                'quantity_needed' => $productItem->quantity_needed,
                'unit' => $productItem->item->unit, // Get unit from items table
                'cost_per_unit' => $itemCost,
                'total_cost' => $itemTotalCost,
                'is_critical' => $productItem->is_critical,
                'notes' => $productItem->notes,
            ];
        }
        
        return [
            'items' => $breakdown,
            'total_hpp' => round($totalCost, 2),
            'method' => $method,
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get latest purchase cost for a product item (fallback method)
     */
    private function getLatestPurchaseCost($productItem)
    {
        // Since purchase_items table now uses item_id, find purchase cost for the specific item
        $latestPurchase = \App\Models\PurchaseItem::where('item_id', $productItem->item_id)
            ->latest('created_at')
            ->first();
            
        return $latestPurchase?->unit_cost;
    }

    /**
     * Get average purchase cost for a product item (fallback method)
     */
    private function getAveragePurchaseCost($productItem)
    {
        // Since purchase_items table now uses item_id, find average purchase cost for the specific item
        $averageCost = \App\Models\PurchaseItem::where('item_id', $productItem->item_id)
            ->avg('unit_cost');
            
        return $averageCost;
    }

    /**
     * Get image URL dengan fallback
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            // Jika menggunakan storage public dengan subdirectory products
            return asset('storage/products/' . $this->image);
        }
        
        return null;
    }

    /**
     * Check apakah produk memiliki stok rendah
     */
    public function hasLowStock(): bool
    {
        $inventory = $this->inventory()->first();
        if (!$inventory) return false;
        
        return $inventory->current_stock <= $inventory->reorder_level && $inventory->current_stock > 0;
    }

    /**
     * Check apakah produk kehabisan stok
     */
    public function isOutOfStock(): bool
    {
        $inventory = $this->inventory()->first();
        if (!$inventory) return true;
        
        return $inventory->current_stock <= 0;
    }

    /**
     * Get current stock from inventory
     */
    public function getCurrentStock(): int
    {
        $inventory = $this->inventory()->first();
        return $inventory ? $inventory->current_stock : 0;
    }

    /**
     * Get status stok produk
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        } elseif ($this->hasLowStock()) {
            return 'low_stock';
        } else {
            return 'in_stock';
        }
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCostAttribute(): string
    {
        return $this->cost ? 'Rp ' . number_format($this->cost, 0, ',', '.') : '-';
    }

    /**
     * Get profit margin (price - hpp)
     */
    public function getProfitMarginAttribute(): float
    {
        return round($this->price - $this->hpp, 2);
    }

    /**
     * Get total stock value
     */
    public function getStockValueAttribute(): float
    {
        return $this->stock * $this->price;
    }

    /**
     * Update stok produk melalui inventory table
     */
    public function updateStock(int $quantity, string $type = 'set'): bool
    {
        $inventory = $this->inventory()->first();
        
        if (!$inventory) {
            // Create inventory record if doesn't exist
            $inventory = Inventory::create([
                'id_product' => $this->id_product,
                'current_stock' => 0,
                'reserved_stock' => 0,
                'reorder_level' => 10,
                'average_cost' => $this->cost ?? 0,
                'created_by' => auth()->id() ?? 1,
            ]);
        }
        
        $newStock = $inventory->current_stock;
        
        switch ($type) {
            case 'add':
                $newStock += $quantity;
                break;
            case 'subtract':
                $newStock = max(0, $newStock - $quantity);
                break;
            case 'set':
            default:
                $newStock = max(0, $quantity);
                break;
        }
        
        return $inventory->update(['current_stock' => $newStock]);
    }

    /**
     * Toggle status aktif produk
     */
    public function toggleActive(): bool
    {
        $this->active = !$this->active;
        return $this->save();
    }

    /**
     * Toggle status unggulan produk
     */
    public function toggleFeatured(): bool
    {
        $this->featured = !$this->featured;
        return $this->save();
    }
}
