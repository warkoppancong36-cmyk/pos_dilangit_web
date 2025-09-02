<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orders';
    protected $primaryKey = 'id_order';

    protected $fillable = [
        'order_number',
        'id_customer',
        'id_user',
        'id_shift',
        'order_type',
        'status',
        'table_number',
        'guest_count',
        'subtotal',
        'discount_amount',
        'discount_type',
        'tax_amount',
        'service_charge',
        'total_amount',
        'notes',
        'customer_info',
        'order_date',
        'prepared_at',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'guest_count' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'customer_info' => 'array',
        'order_date' => 'datetime',
        'prepared_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_total_amount',
        'formatted_subtotal',
        'order_status_text',
        'order_type_text',
        'is_paid',
        'total_paid',
        'remaining_amount',
    ];

    // Boot method untuk auto-generate order number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = $order->generateOrderNumber();
            }

            if (empty($order->order_date)) {
                $order->order_date = now();
            }
        });
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = 'ORD-' . $date . '-';
        
        $lastOrder = static::where('order_number', 'like', $prefix . '%')
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) substr($lastOrder->order_number, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'id_customer', 'id_customer');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'id_shift', 'id_shift');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'id_order', 'id_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'id_order', 'id_order');
    }

    // Accessors
    public function getFormattedTotalAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    public function getOrderStatusTextAttribute(): string
    {
        $statuses = [
            'draft' => 'Draft',
            'pending' => 'Menunggu',
            'preparing' => 'Sedang Disiapkan',
            'ready' => 'Siap',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];

        return $statuses[$this->status] ?? ($this->status ?? 'Unknown');
    }

    public function getOrderTypeTextAttribute(): string
    {
        $types = [
            'dine_in' => 'Dine In',
            'takeaway' => 'Takeaway',
            'delivery' => 'Delivery'
        ];

        return $types[$this->order_type] ?? ($this->order_type ?? 'Unknown');
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->total_paid >= $this->total_amount;
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->total_paid);
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByOrderType($query, $type)
    {
        return $query->where('order_type', $type);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('order_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('order_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('order_date', now()->month)
                    ->whereYear('order_date', now()->year);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'preparing', 'ready']);
    }

    // Methods
    public function calculateTotals()
    {
        $this->subtotal = $this->orderItems()->sum('total_price');
        
        // Calculate tax (using PPN if exists)
        $ppn = \App\Models\Ppn::first();
        $taxRate = $ppn ? $ppn->rate : 0; // FIXED: Remove /100 since rate is already decimal
        $this->tax_amount = $this->subtotal * $taxRate;

        // Calculate total
        $this->total_amount = $this->subtotal + $this->tax_amount + $this->service_charge - $this->discount_amount;
        
        $this->save();
    }

    public function addItem($productId, $quantity = 1, $price = null, $notes = null)
    {
        // Get product details
        $product = \App\Models\Product::find($productId);
        $itemPrice = $price ?? $product->price;
        $itemName = $product->name;

        // Check if item already exists in order
        $existingItem = $this->orderItems()
            ->where('id_product', $productId)
            ->first();

        if ($existingItem) {
            // Update existing item
            $existingItem->quantity += $quantity;
            $existingItem->total_price = $existingItem->quantity * $existingItem->unit_price;
            $existingItem->save();
            return $existingItem;
        } else {
            // Create new item
            $itemSku = $product->sku ?? 'NO-SKU';
            
            return $this->orderItems()->create([
                'id_product' => $productId,
                'item_name' => $itemName,
                'item_sku' => $itemSku,
                'quantity' => $quantity,
                'unit_price' => $itemPrice,
                'total_price' => $quantity * $itemPrice,
                'notes' => $notes,
            ]);
        }
    }

    public function removeItem($orderItemId)
    {
        $item = $this->orderItems()->find($orderItemId);
        if ($item) {
            $item->delete();
            // $this->calculateTotals(); // DISABLED - tax system not used yet
            return true;
        }
        return false;
    }

    public function updateStatus($status, $userId = null)
    {
        $this->status = $status;
        
        if ($status === 'preparing' && !$this->prepared_at) {
            $this->prepared_at = now();
        }
        
        if ($status === 'completed' && !$this->completed_at) {
            $this->completed_at = now();
        }

        if ($userId) {
            $this->updated_by = $userId;
        }

        $this->save();
    }

    /**
     * Get daily order sequence number
     */
    public function getDailyOrderSequenceAttribute(): int
    {
        return $this->getDailyOrderSequence();
    }

    /**
     * Calculate daily order sequence
     */
    public function getDailyOrderSequence(): int
    {
        $startOfDay = $this->created_at->startOfDay();
        $endOfDay = $this->created_at->endOfDay();
        
        return static::where('created_at', '<', $this->created_at)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count() + 1;
    }

    /**
     * Get daily order sequence for a specific date
     */
    public static function getDailyOrderSequenceForDate($date = null): int
    {
        if (!$date) {
            $date = now();
        }

        $startOfDay = \Carbon\Carbon::parse($date)->startOfDay();
        $endOfDay = \Carbon\Carbon::parse($date)->endOfDay();
        
        return static::whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count() + 1;
    }
}
