<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Discount extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'discounts';
    protected $primaryKey = 'id_discount';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_amount',
        'maximum_discount',
        'usage_limit',
        'usage_limit_per_customer',
        'used_count',
        'valid_from',
        'valid_until',
        'active',
        'applicable_products',
        'applicable_categories',
        'customer_groups',
        'conditions',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_discount' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'active' => 'boolean',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'customer_groups' => 'array',
        'conditions' => 'array',
        'used_count' => 'integer',
        'usage_limit' => 'integer',
        'usage_limit_per_customer' => 'integer'
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeValid($query)
    {
        $now = now();
        return $query->where('valid_from', '<=', $now)
                    ->where('valid_until', '>=', $now);
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
              ->orWhere('used_count', '<', 'usage_limit');
        });
    }

    // Methods
    public function isValid(): bool
    {
        $now = now();
        return $this->active && 
               $this->valid_from <= $now && 
               $this->valid_until >= $now;
    }

    public function isUsageLimitReached(): bool
    {
        if (!$this->usage_limit) {
            return false;
        }
        return $this->used_count >= $this->usage_limit;
    }

    public function canBeUsedBy($customerId, $orderTotal): bool
    {
        // Check if discount is valid and active
        if (!$this->isValid()) {
            return false;
        }

        // Check usage limit
        if ($this->isUsageLimitReached()) {
            return false;
        }

        // Check minimum amount
        if ($this->minimum_amount && $orderTotal < $this->minimum_amount) {
            return false;
        }

        // Check customer usage limit per customer
        if ($this->usage_limit_per_customer && $customerId) {
            // TODO: Implement customer usage tracking
        }

        return true;
    }

    public function calculateDiscount($orderTotal, $products = []): float
    {
        if (!$this->canBeUsedBy(null, $orderTotal)) {
            return 0;
        }

        $discountAmount = 0;

        switch ($this->type) {
            case 'percentage':
                $discountAmount = ($orderTotal * $this->value) / 100;
                break;
                
            case 'fixed_amount':
                $discountAmount = $this->value;
                break;
                
            case 'buy_x_get_y':
                // Implement buy X get Y logic based on conditions
                $discountAmount = $this->calculateBuyXGetYDiscount($products);
                break;
        }

        // Apply maximum discount limit
        if ($this->maximum_discount && $discountAmount > $this->maximum_discount) {
            $discountAmount = $this->maximum_discount;
        }

        return round($discountAmount, 2);
    }

    private function calculateBuyXGetYDiscount($products): float
    {
        // Implement complex buy X get Y logic
        // This would depend on the conditions array structure
        return 0;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    // Attributes
    public function getStatusAttribute(): string
    {
        if (!$this->active) {
            return 'inactive';
        }

        $now = now();
        if ($now < $this->valid_from) {
            return 'scheduled';
        }

        if ($now > $this->valid_until) {
            return 'expired';
        }

        if ($this->isUsageLimitReached()) {
            return 'exhausted';
        }

        return 'active';
    }

    public function getFormattedValueAttribute(): string
    {
        switch ($this->type) {
            case 'percentage':
                return $this->value . '%';
            case 'fixed_amount':
                return 'Rp ' . number_format($this->value, 0, ',', '.');
            default:
                return $this->value;
        }
    }

    public function getRemainingUsageAttribute(): ?int
    {
        if (!$this->usage_limit) {
            return null;
        }
        return max(0, $this->usage_limit - $this->used_count);
    }
}
