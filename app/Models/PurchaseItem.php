<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchase_items';
    protected $primaryKey = 'id_purchase_item';

    protected $fillable = [
        'purchase_id',
        'item_id',
        'quantity_ordered',
        'quantity_received',
        'unit',
        'unit_cost',
        'total_cost',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'notes',
        'quality_check',
        'received_by'
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:3',
        'quantity_received' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'quality_check' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id', 'id_purchase');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // DISABLED - Variant system removed
    // public function variant()
    // {
    //     return $this->belongsTo(Variant::class, 'id_variant', 'id_variant');
    // }

    // Accessors
    public function getFormattedUnitCostAttribute()
    {
        return 'Rp ' . number_format($this->unit_cost, 0, ',', '.');
    }

    public function getFormattedTotalCostAttribute()
    {
        return 'Rp ' . number_format($this->total_cost, 0, ',', '.');
    }

    public function getProductNameAttribute()
    {
        return $this->variant ? $this->variant->product->name : '';
    }

    public function getVariantNameAttribute()
    {
        if (!$this->variant) return '';
        
        $variantName = $this->variant->name;
        if ($this->variant->size) {
            $variantName .= ' - ' . $this->variant->size;
        }
        
        return $variantName;
    }

    public function getSkuAttribute()
    {
        return $this->variant ? $this->variant->sku : '';
    }

    public function getUnitAttribute()
    {
        return $this->variant ? $this->variant->unit : 'pcs';
    }

    // Helper methods
    public function getRemainingQuantity()
    {
        return $this->quantity_ordered - ($this->quantity_received ?? 0);
    }

    public function getReceivedPercentage()
    {
        if ($this->quantity_ordered == 0) return 0;
        return (($this->quantity_received ?? 0) / $this->quantity_ordered) * 100;
    }

    public function isFullyReceived()
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }

    public function isPartiallyReceived()
    {
        return ($this->quantity_received ?? 0) > 0 && $this->quantity_received < $this->quantity_ordered;
    }

    // Mutators
    public function setTotalCostAttribute($value)
    {
        $this->attributes['total_cost'] = $value ?? ($this->quantity_ordered * $this->unit_cost);
    }

    // Boot method to automatically calculate total_cost
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($purchaseItem) {
            if (!$purchaseItem->total_cost) {
                $purchaseItem->total_cost = $purchaseItem->quantity_ordered * $purchaseItem->unit_cost;
            }
        });

        static::updating(function ($purchaseItem) {
            if ($purchaseItem->isDirty(['quantity_ordered', 'unit_cost']) && !$purchaseItem->isDirty('total_cost')) {
                $purchaseItem->total_cost = $purchaseItem->quantity_ordered * $purchaseItem->unit_cost;
            }
        });
    }
}
