<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenOrder extends Model
{
    use HasFactory;

    protected $table = 'kitchen_orders';
    protected $primaryKey = 'id_kitchen_order';

    protected $fillable = [
        'id_order',
        'order_number',
        'table_number',
        'order_type',
        'customer_name',
        'status',
        'created_by_station',
        'acknowledged_at',
        'completed_at',
        'printed_at',
        'notes',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'completed_at' => 'datetime',
        'printed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'status_text',
        'status_color',
        'elapsed_time',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($kitchenOrder) {
            if (empty($kitchenOrder->status)) {
                $kitchenOrder->status = self::STATUS_PENDING;
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order', 'id_order');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KitchenOrderItem::class, 'id_kitchen_order', 'id_kitchen_order');
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_IN_PROGRESS => 'Diproses',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'error',
            default => 'grey',
        };
    }

    public function getElapsedTimeAttribute(): array
    {
        $now = now();
        $created = $this->created_at;
        
        $diffInMinutes = $created->diffInMinutes($now);
        
        return [
            'minutes' => $diffInMinutes,
            'display' => $diffInMinutes . ' menit',
            'urgency' => $this->getUrgencyLevel($diffInMinutes),
        ];
    }

    private function getUrgencyLevel(int $minutes): string
    {
        if ($minutes < 5) {
            return 'normal';
        } elseif ($minutes < 10) {
            return 'warning';
        } else {
            return 'urgent';
        }
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeNotPrinted($query)
    {
        return $query->whereNull('printed_at');
    }

    public function acknowledge(): bool
    {
        $this->status = self::STATUS_IN_PROGRESS;
        $this->acknowledged_at = now();
        return $this->save();
    }

    public function complete(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        if (!$this->acknowledged_at) {
            $this->acknowledged_at = now();
        }
        $this->completed_at = now();
        return $this->save();
    }

    public function markAsPrinted(): bool
    {
        $this->printed_at = now();
        return $this->save();
    }

    public function cancel(): bool
    {
        $this->status = self::STATUS_CANCELLED;
        return $this->save();
    }

    /**
     * Add items to an existing kitchen order
     * Wrapped in DB::transaction so ALL items succeed or ALL are rolled back
     */
    public function addItems(array $items): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                \Log::info('Creating kitchen order item', [
                    'kitchen_order_id' => $this->id_kitchen_order,
                    'product_name' => $item['product_name'] ?? 'Unknown',
                    'quantity' => $item['quantity'] ?? 1,
                ]);

                KitchenOrderItem::create([
                    'id_kitchen_order' => $this->id_kitchen_order,
                    'id_order_item' => $item['id_order_item'] ?? null,
                    'product_name' => $item['product_name'] ?? $item['item_name'] ?? 'Unknown Product',
                    'quantity' => $item['quantity'] ?? 1,
                    'variant_name' => $item['variant_name'] ?? null,
                    'customizations' => $item['customizations'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }
        });
    }

    /**
     * Find or create kitchen order for an order
     * Uses lockForUpdate to prevent race condition when multiple items are added simultaneously
     */
    public static function findOrCreateForOrder(Order $order, array $items, string $station = 'kasir'): self
    {
        // Use lockForUpdate to prevent race condition (two simultaneous requests both creating new kitchen orders)
        $kitchenOrder = self::where('id_order', $order->id_order)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
            ->orderBy('created_at', 'desc')
            ->lockForUpdate()
            ->first();

        if ($kitchenOrder) {
            // Add items to existing kitchen order
            $kitchenOrder->addItems($items);
            \Log::info('Items added to existing kitchen order', [
                'kitchen_order_id' => $kitchenOrder->id_kitchen_order,
                'order_id' => $order->id_order,
                'order_number' => $order->order_number,
                'new_items_count' => count($items),
            ]);
        } else {
            // Create new kitchen order with items
            $kitchenOrder = self::createFromOrderItems($order, $items, $station);
        }

        return $kitchenOrder;
    }

    public static function createFromOrderItems(Order $order, array $items, string $station = 'kasir'): self
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($order, $items, $station) {
            $kitchenOrder = self::create([
                'id_order' => $order->id_order,
                'order_number' => $order->order_number,
                'table_number' => $order->table_number,
                'order_type' => $order->order_type,
                'customer_name' => $order->customer ? $order->customer->name : ($order->customer_info['name'] ?? 'Walk-in Customer'),
                'status' => self::STATUS_PENDING,
                'created_by_station' => $station,
                'notes' => $order->notes,
            ]);

            foreach ($items as $item) {
                \Log::info('Creating kitchen order item (new order)', [
                    'kitchen_order_id' => $kitchenOrder->id_kitchen_order,
                    'product_name' => $item['product_name'] ?? 'Unknown',
                    'quantity' => $item['quantity'] ?? 1,
                ]);

                KitchenOrderItem::create([
                    'id_kitchen_order' => $kitchenOrder->id_kitchen_order,
                    'id_order_item' => $item['id_order_item'] ?? null,
                    'product_name' => $item['product_name'] ?? $item['item_name'] ?? 'Unknown Product',
                    'quantity' => $item['quantity'] ?? 1,
                    'variant_name' => $item['variant_name'] ?? null,
                    'customizations' => $item['customizations'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'status' => 'pending',
                ]);
            }

            return $kitchenOrder;
        });
    }
}
