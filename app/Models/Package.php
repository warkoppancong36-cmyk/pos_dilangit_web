<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Package extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'packages';
    protected $primaryKey = 'id_package';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sku',
        'barcode',
        'image',
        'package_type',
        'regular_price',
        'package_price',
        'savings_amount',
        'savings_percentage',
        'category_id',
        'is_active',
        'is_featured',
        'status',
        'stock',
        'track_stock',
        'tags',
        'sort_order',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'regular_price' => 'decimal:2',
        'package_price' => 'decimal:2',
        'savings_amount' => 'decimal:2',
        'savings_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'track_stock' => 'boolean',
        'tags' => 'array',
        'stock' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'formatted_package_price',
        'formatted_regular_price',
        'formatted_savings',
        'items_count',
        'availability_status',
    ];

    // ==================== Relationships ====================

    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class, 'id_package', 'id_package')
            ->orderBy('sort_order');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id_category');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ==================== Accessors ====================

    public function getFormattedPackagePriceAttribute(): string
    {
        return 'Rp ' . number_format($this->package_price, 0, ',', '.');
    }

    public function getFormattedRegularPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->regular_price, 0, ',', '.');
    }

    public function getFormattedSavingsAttribute(): string
    {
        return 'Rp ' . number_format($this->savings_amount, 0, ',', '.');
    }

    public function getItemsCountAttribute(): int
    {
        return $this->items()->count();
    }

    public function getAvailabilityStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if ($this->track_stock && $this->stock <= 0) {
            return 'out_of_stock';
        }

        // Check if all items are available
        $allItemsAvailable = $this->items->every(function ($item) {
            return $item->product && $item->product->active && $item->product->stock >= $item->quantity;
        });

        if (!$allItemsAvailable) {
            return 'unavailable';
        }

        return 'available';
    }

    // ==================== Scopes ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where('track_stock', false)
                  ->orWhere(function ($q2) {
                      $q2->where('track_stock', true)->where('stock', '>', 0);
                  });
            });
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // ==================== Methods ====================

    /**
     * Calculate and update pricing based on items
     */
    public function calculatePricing(): void
    {
        $regularPrice = $this->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });

        $this->regular_price = $regularPrice;
        
        if ($this->package_price > 0) {
            $this->savings_amount = $regularPrice - $this->package_price;
            $this->savings_percentage = $regularPrice > 0 
                ? ($this->savings_amount / $regularPrice) * 100 
                : 0;
        }
    }

    /**
     * Check if package is available (all items in stock)
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active || $this->status !== 'published') {
            return false;
        }

        if ($this->track_stock && $this->stock <= 0) {
            return false;
        }

        // Check all items
        foreach ($this->items as $item) {
            if (!$item->product || !$item->product->active) {
                return false;
            }

            if ($item->product->stock < $item->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reduce stock of package and its items
     */
    public function reduceStock(int $quantity = 1): bool
    {
        if ($this->track_stock) {
            if ($this->stock < $quantity) {
                return false;
            }
            $this->stock -= $quantity;
            $this->save();
        }

        // Reduce stock of all items
        foreach ($this->items as $item) {
            $requiredQuantity = $item->quantity * $quantity;
            
            if ($item->product->stock < $requiredQuantity) {
                return false;
            }
            
            $item->product->stock -= $requiredQuantity;
            $item->product->save();
        }

        return true;
    }

    // ==================== Events ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($package) {
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->name);
            }
        });

        static::updating(function ($package) {
            if ($package->isDirty('name')) {
                $package->slug = Str::slug($package->name);
            }
        });
    }
}
