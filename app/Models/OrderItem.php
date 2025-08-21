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
        'notes',
        'customizations',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_unit_price',
        'formatted_total_price',
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
        $this->total_price = $this->quantity * $this->unit_price - $this->discount_amount;
        $this->save();
        
        // Recalculate order totals
        $this->order->calculateTotals();
    }

    public function applyDiscount($amount)
    {
        $this->discount_amount = $amount;
        $this->total_price = ($this->quantity * $this->unit_price) - $amount;
        $this->save();
        
        // Recalculate order totals
        $this->order->calculateTotals();
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($orderItem) {
            // Auto-calculate total price if not set
            if (!$orderItem->total_price) {
                $orderItem->total_price = ($orderItem->quantity * $orderItem->unit_price) - $orderItem->discount_amount;
                $orderItem->saveQuietly(); // Prevent infinite loop
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
