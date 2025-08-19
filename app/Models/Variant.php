<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Variant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'variants';
    protected $primaryKey = 'id_variant';

    protected $fillable = [
        'id_product',
        'sku',
        'name',
        'variant_values',
        'price',
        'cost_price',
        'barcode',
        'image',
        'active',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'variant_values' => 'array',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_price',
        'profit_margin',
        'variant_display_name',
        'stock_info',
        'composition_summary',
        'id'  // Add id accessor for frontend compatibility
    ];

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'id_variant', 'id_variant');
    }

    public function variantItems(): HasMany
    {
        return $this->hasMany(VariantItem::class, 'id_variant', 'id_variant')
                    ->with(['item.inventory']);
    }

    public function activeVariantItems(): HasMany
    {
        return $this->hasMany(VariantItem::class, 'id_variant', 'id_variant')
                    ->where('active', true)
                    ->with(['item.inventory']);
    }

    public function criticalVariantItems(): HasMany
    {
        return $this->hasMany(VariantItem::class, 'id_variant', 'id_variant')
                    ->where('is_critical', true)
                    ->with(['item.inventory']);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Accessors
     */
    public function getIdAttribute(): int
    {
        return $this->id_variant;
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost_price || $this->cost_price == 0) {
            return null;
        }
        $margin = (($this->price - $this->cost_price) / $this->cost_price) * 100;
        return ceil($margin);
    }

    public function getVariantDisplayNameAttribute(): string
    {
        $values = [];
        
        // Ensure variant_values is array and not empty
        if (!is_array($this->variant_values) || empty($this->variant_values)) {
            return 'Default Variant';
        }
        
        foreach ($this->variant_values as $key => $value) {
            $values[] = ucfirst($key) . ': ' . $value;
        }
        return implode(', ', $values);
    }

    public function getStockInfoAttribute(): array
    {
        $inventory = $this->inventory()->first();
        return [
            'current_stock' => $inventory->current_stock ?? 0,
            'reserved_stock' => $inventory->reserved_stock ?? 0,
            'available_stock' => $inventory->available_stock ?? 0,
            'reorder_level' => $inventory->reorder_level ?? 0,
        ];
    }

    public function getCompositionSummaryAttribute(): array
    {
        $items = $this->variantItems;
        $summary = [
            'total_items' => $items->count(),
            'critical_items' => $items->where('is_critical', true)->count(),
            'total_cost' => $items->sum('total_cost'),
            'stock_status' => 'sufficient'
        ];

        // Check stock status
        foreach ($items as $item) {
            if ($item->stock_status['status'] === 'out') {
                $summary['stock_status'] = 'out';
                break;
            } elseif ($item->stock_status['status'] === 'low') {
                $summary['stock_status'] = 'low';
            }
        }

        return $summary;
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

    public function scopeByProduct($query, $productId)
    {
        return $query->where('id_product', $productId);
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%")
              ->orWhere('barcode', 'like', "%{$search}%")
              ->orWhereHas('product', function ($productQuery) use ($search) {
                  $productQuery->where('name', 'like', "%{$search}%");
              });
        });
    }

    public function scopeByVariantValue($query, $key, $value)
    {
        return $query->whereJsonContains('variant_values->' . $key, $value);
    }

    public function scopeLowStock($query, $threshold = null)
    {
        return $query->whereHas('inventory', function ($q) use ($threshold) {
            if ($threshold) {
                $q->where('current_stock', '<=', $threshold);
            } else {
                $q->whereRaw('current_stock <= reorder_level');
            }
        });
    }

    public function scopeWithComposition($query)
    {
        return $query->whereHas('variantItems');
    }

    public function scopeWithoutComposition($query)
    {
        return $query->whereDoesntHave('variantItems');
    }

    /**
     * Helper Methods
     */
    public function generateSku(): string
    {
        $product = $this->product;
        if (!$product) {
            return 'VAR-' . strtoupper(uniqid());
        }

        $productCode = strtoupper(substr($product->name, 0, 3));
        $variantCode = '';
        
        foreach ($this->variant_values as $key => $value) {
            $variantCode .= strtoupper(substr($value, 0, 1));
        }
        
        return $productCode . '-' . $variantCode . '-' . $this->id_variant;
    }

    public function updateStock($quantity, $type = 'add'): bool
    {
        $inventory = $this->inventory()->first();
        if (!$inventory) {
            return false;
        }

        if ($type === 'add') {
            $inventory->current_stock += $quantity;
        } else {
            $inventory->current_stock -= $quantity;
        }

        return $inventory->save();
    }

    public function isLowStock(): bool
    {
        $inventory = $this->inventory()->first();
        if (!$inventory) {
            return false;
        }

        return $inventory->current_stock <= $inventory->reorder_level;
    }

    public function canFulfillOrder($quantity): bool
    {
        $inventory = $this->inventory()->first();
        if (!$inventory) {
            return false;
        }

        return $inventory->available_stock >= $quantity;
    }

    public function calculateCostFromComposition(): float
    {
        $totalCost = 0;
        foreach ($this->variantItems as $variantItem) {
            $totalCost += $variantItem->total_cost;
        }
        return $totalCost;
    }

    public function canProduce($quantity = 1): array
    {
        $canProduce = true;
        $limitingItems = [];
        $details = [];

        foreach ($this->variantItems as $variantItem) {
            $item = $variantItem->item;
            $needed = $variantItem->quantity_needed * $quantity;
            $available = $item->inventory->current_stock ?? 0;
            $maxProducible = $needed > 0 ? floor($available / $variantItem->quantity_needed) : 0;

            $details[] = [
                'item_name' => $item->name,
                'needed_per_unit' => $variantItem->quantity_needed,
                'total_needed' => $needed,
                'available_stock' => $available,
                'max_producible' => $maxProducible
            ];

            if ($available < $needed) {
                $canProduce = false;
                $limitingItems[] = $item->name;
            }
        }

        return [
            'can_produce' => $canProduce,
            'limiting_items' => $limitingItems,
            'details' => $details
        ];
    }
}
