<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageItem extends Model
{
    use HasFactory;

    protected $table = 'package_items';
    protected $primaryKey = 'id_package_item';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'id_package',
        'id_product',
        'quantity',
        'unit',
        'is_optional',
        'is_default_selected',
        'unit_price',
        'subtotal',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_optional' => 'boolean',
        'is_default_selected' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'formatted_subtotal',
        'formatted_unit_price',
    ];

    // ==================== Relationships ====================

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'id_package', 'id_package');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    // ==================== Accessors ====================

    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal ?? 0, 0, ',', '.');
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_price ?? 0, 0, ',', '.');
    }

    // ==================== Methods ====================

    /**
     * Calculate subtotal based on quantity and unit price
     */
    public function calculateSubtotal(): void
    {
        if ($this->unit_price !== null && $this->quantity !== null) {
            $this->subtotal = $this->unit_price * $this->quantity;
        }
    }

    /**
     * Sync unit price from product
     */
    public function syncPriceFromProduct(): void
    {
        if ($this->product) {
            $this->unit_price = $this->product->price;
            $this->calculateSubtotal();
        }
    }

    // ==================== Events ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Auto sync price if not set
            if ($item->unit_price === null && $item->product) {
                $item->unit_price = $item->product->price;
            }
            
            // Calculate subtotal
            $item->calculateSubtotal();
        });

        static::updating(function ($item) {
            // Recalculate if quantity or price changes
            if ($item->isDirty(['quantity', 'unit_price'])) {
                $item->calculateSubtotal();
            }
        });
    }
}
