<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';
    protected $primaryKey = 'id_order_item';

    protected $fillable = [
        'id_order',
        'id_product',
        'id_variant', // ENABLED - Variant system for order items
        'item_name',
        'item_sku',
        'quantity',
        'unit_price',
        'total_price',
        'discount_amount',
        'discount_type',
        'discount_percentage',
        'subtotal_before_discount',
        'notes',
        'customizations',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'subtotal_before_discount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_unit_price',
        'formatted_total_price',
        'formatted_discount_amount',
        'item_code',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order', 'id_order');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'id_variant', 'id_variant');
    }

    // Accessors
    public function getFormattedUnitPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    public function getItemCodeAttribute(): string
    {
        return $this->product->sku ?? '';
    }

    // Methods
    public function updateQuantity($quantity)
    {
        $this->quantity = $quantity;
        $this->calculateTotalPrice();
        $this->save();
        
        // Recalculate order totals
        $this->order->calculateTotals();
    }

    public function applyDiscount($amount, $type = 'fixed')
    {
        $this->discount_type = $type;
        
        if ($type === 'percentage') {
            $this->discount_percentage = $amount;
            $subtotal = $this->quantity * $this->unit_price;
            $this->discount_amount = ($subtotal * $amount) / 100;
        } else {
            $this->discount_amount = $amount;
            $this->discount_percentage = null;
        }
        
        $this->calculateTotalPrice();
        $this->save();
        
        // Recalculate order totals
        $this->order->calculateTotals();
    }

    public function calculateTotalPrice()
    {
        $subtotal = $this->quantity * $this->unit_price;
        $this->subtotal_before_discount = $subtotal;
        $this->total_price = $subtotal - $this->discount_amount;
        
        return $this->total_price;
    }

    public function getDiscountPercentageAttribute($value)
    {
        if ($this->discount_type === 'percentage') {
            return $value;
        }
        
        // Calculate percentage from fixed amount
        if ($this->subtotal_before_discount > 0) {
            return ($this->discount_amount / $this->subtotal_before_discount) * 100;
        }
        
        return 0;
    }

    public function getFormattedDiscountAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->discount_amount, 0, ',', '.');
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            // Auto-calculate total price
            $orderItem->calculateTotalPrice();
        });

        static::saved(function ($orderItem) {
            // Recalculate order totals
            if ($orderItem->order) {
                $orderItem->order->calculateTotals();
            }
        });

        static::deleted(function ($orderItem) {
            // Recalculate order totals when item is deleted
            if ($orderItem->order) {
                $orderItem->order->calculateTotals();
            }
        });
    }
}
