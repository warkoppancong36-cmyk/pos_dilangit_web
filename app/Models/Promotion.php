<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promotions';
    protected $primaryKey = 'id_promotion';

    protected $fillable = [
        'name',
        'description',
        'type',
        'promotion_rules',
        'discount_value',
        'discount_type',
        'valid_from',
        'valid_until',
        'valid_days',
        'valid_time_from',
        'valid_time_until',
        'active',
        'priority',
        'applicable_products',
        'applicable_categories',
        'conditions',
        'banner_image',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'promotion_rules' => 'array',
        'discount_value' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'valid_days' => 'array',
        'valid_time_from' => 'datetime:H:i',
        'valid_time_until' => 'datetime:H:i',
        'active' => 'boolean',
        'priority' => 'integer',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'conditions' => 'array'
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

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    // Methods
    public function isValid(): bool
    {
        $now = now();
        
        // Check date validity
        if (!$this->active || $this->valid_from > $now || $this->valid_until < $now) {
            return false;
        }

        // Check day validity
        if ($this->valid_days && !empty($this->valid_days)) {
            $currentDay = strtolower($now->format('l'));
            if (!in_array($currentDay, $this->valid_days)) {
                return false;
            }
        }

        // Check time validity
        if ($this->valid_time_from && $this->valid_time_until) {
            $currentTime = $now->format('H:i:s');
            $startTime = $this->valid_time_from->format('H:i:s');
            $endTime = $this->valid_time_until->format('H:i:s');
            
            if ($currentTime < $startTime || $currentTime > $endTime) {
                return false;
            }
        }

        return true;
    }

    public function appliesToProduct($productId): bool
    {
        if (!$this->applicable_products) {
            return true; // Apply to all products if not specified
        }

        return in_array($productId, $this->applicable_products);
    }

    public function appliesToCategory($categoryId): bool
    {
        if (!$this->applicable_categories) {
            return true; // Apply to all categories if not specified
        }

        return in_array($categoryId, $this->applicable_categories);
    }

    public function calculatePromotion($cartItems): array
    {
        if (!$this->isValid()) {
            return ['discount' => 0, 'description' => '', 'applied_items' => []];
        }

        $result = [
            'discount' => 0,
            'description' => '',
            'applied_items' => []
        ];

        switch ($this->type) {
            case 'happy_hour':
                $result = $this->calculateHappyHourDiscount($cartItems);
                break;
                
            case 'buy_one_get_one':
                $result = $this->calculateBogoDiscount($cartItems);
                break;
                
            case 'combo_deal':
                $result = $this->calculateComboDiscount($cartItems);
                break;
                
            case 'member_discount':
                $result = $this->calculateMemberDiscount($cartItems);
                break;
                
            case 'seasonal':
                $result = $this->calculateSeasonalDiscount($cartItems);
                break;
        }

        return $result;
    }

    private function calculateHappyHourDiscount($cartItems): array
    {
        $discount = 0;
        $appliedItems = [];
        
        foreach ($cartItems as $item) {
            if ($this->appliesToProduct($item['product_id'])) {
                $itemDiscount = 0;
                
                if ($this->discount_type === 'percentage') {
                    $itemDiscount = ($item['price'] * $item['quantity'] * $this->discount_value) / 100;
                } else {
                    $itemDiscount = $this->discount_value * $item['quantity'];
                }
                
                $discount += $itemDiscount;
                $appliedItems[] = $item['product_id'];
            }
        }

        return [
            'discount' => round($discount, 2),
            'description' => "Happy Hour: {$this->discount_value}" . ($this->discount_type === 'percentage' ? '%' : ' off'),
            'applied_items' => $appliedItems
        ];
    }

    private function calculateBogoDiscount($cartItems): array
    {
        $discount = 0;
        $appliedItems = [];
        
        $rules = $this->promotion_rules;
        $buyQuantity = $rules['buy_quantity'] ?? 1;
        $getQuantity = $rules['get_quantity'] ?? 1;
        
        foreach ($cartItems as $item) {
            if ($this->appliesToProduct($item['product_id'])) {
                $eligibleSets = floor($item['quantity'] / $buyQuantity);
                $freeItems = $eligibleSets * $getQuantity;
                
                if ($freeItems > 0) {
                    $itemDiscount = $item['price'] * $freeItems;
                    $discount += $itemDiscount;
                    $appliedItems[] = $item['product_id'];
                }
            }
        }

        return [
            'discount' => round($discount, 2),
            'description' => "Buy {$buyQuantity} Get {$getQuantity} Free",
            'applied_items' => $appliedItems
        ];
    }

    private function calculateComboDiscount($cartItems): array
    {
        // Implement combo deal logic based on promotion_rules
        return [
            'discount' => 0,
            'description' => 'Combo Deal',
            'applied_items' => []
        ];
    }

    private function calculateMemberDiscount($cartItems): array
    {
        $discount = 0;
        $appliedItems = [];
        
        foreach ($cartItems as $item) {
            if ($this->appliesToProduct($item['product_id'])) {
                $itemDiscount = 0;
                
                if ($this->discount_type === 'percentage') {
                    $itemDiscount = ($item['price'] * $item['quantity'] * $this->discount_value) / 100;
                } else {
                    $itemDiscount = $this->discount_value * $item['quantity'];
                }
                
                $discount += $itemDiscount;
                $appliedItems[] = $item['product_id'];
            }
        }

        return [
            'discount' => round($discount, 2),
            'description' => "Member Discount: {$this->discount_value}" . ($this->discount_type === 'percentage' ? '%' : ' off'),
            'applied_items' => $appliedItems
        ];
    }

    private function calculateSeasonalDiscount($cartItems): array
    {
        return $this->calculateHappyHourDiscount($cartItems); // Similar logic
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

        if (!$this->isValid()) {
            return 'not_applicable'; // Wrong day/time
        }

        return 'active';
    }

    public function getFormattedDiscountAttribute(): string
    {
        if (!$this->discount_value) {
            return 'Special Offer';
        }

        switch ($this->discount_type) {
            case 'percentage':
                return $this->discount_value . '% OFF';
            case 'fixed_amount':
                return 'Rp ' . number_format($this->discount_value, 0, ',', '.') . ' OFF';
            default:
                return 'Special Offer';
        }
    }

    public function getValidDaysTextAttribute(): string
    {
        if (!$this->valid_days || empty($this->valid_days)) {
            return 'Every day';
        }

        $days = array_map('ucfirst', $this->valid_days);
        return implode(', ', $days);
    }

    public function getValidTimeTextAttribute(): string
    {
        if (!$this->valid_time_from || !$this->valid_time_until) {
            return 'All day';
        }

        return $this->valid_time_from->format('H:i') . ' - ' . $this->valid_time_until->format('H:i');
    }
}
