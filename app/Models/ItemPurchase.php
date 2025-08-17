<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPurchase extends Model
{
    use HasFactory;

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
        'received_by',
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
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_unit_cost',
        'formatted_total_cost',
        'remaining_quantity',
        'received_percentage',
        'is_overdue',
    ];

    /**
     * Relationships
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class, 'purchase_id', 'id_purchase');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id_item');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by', 'id');
    }

    /**
     * Computed Attributes
     */
    public function getFormattedUnitCostAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_cost, 0, ',', '.');
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return 'Rp ' . number_format($this->total_cost, 0, ',', '.');
    }

    public function getRemainingQuantityAttribute(): float
    {
        return $this->quantity_ordered - $this->quantity_received;
    }

    public function getReceivedPercentageAttribute(): float
    {
        if ($this->quantity_ordered <= 0) {
            return 0;
        }
        return ($this->quantity_received / $this->quantity_ordered) * 100;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->expected_delivery_date && 
               $this->expected_delivery_date < now() && 
               $this->status !== 'received';
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOverdue($query)
    {
        return $query->where('expected_delivery_date', '<', now())
            ->whereNotIn('status', ['received', 'cancelled']);
    }

    /**
     * Methods
     */
    public function receiveQuantity(float $quantity, array $qualityCheck = null): bool
    {
        if ($quantity > $this->remaining_quantity) {
            return false;
        }

        $this->quantity_received += $quantity;
        $this->actual_delivery_date = now();
        $this->received_by = auth()->id();

        if ($qualityCheck) {
            $this->quality_check = array_merge($this->quality_check ?? [], $qualityCheck);
        }

        // Update status
        if ($this->quantity_received >= $this->quantity_ordered) {
            $this->status = 'received';
        } elseif ($this->quantity_received > 0) {
            $this->status = 'partial';
        }

        $result = $this->save();

        // Update item stock
        if ($result && $quantity > 0) {
            $this->item->addStock($quantity, 'Purchase received', $this->id_purchase_item);
        }

        return $result;
    }

    public function cancelItem(string $reason = null): bool
    {
        $this->status = 'cancelled';
        if ($reason) {
            $this->notes = $this->notes ? $this->notes . "\n[Cancelled: $reason]" : "[Cancelled: $reason]";
        }
        return $this->save();
    }

    public function updateExpectedDate(\DateTime $date): bool
    {
        $this->expected_delivery_date = $date;
        return $this->save();
    }
}
