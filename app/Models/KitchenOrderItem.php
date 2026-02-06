<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenOrderItem extends Model
{
    use HasFactory;

    protected $table = 'kitchen_order_items';
    protected $primaryKey = 'id_kitchen_order_item';

    protected $fillable = [
        'id_kitchen_order',
        'id_order_item',
        'product_name',
        'quantity',
        'variant_name',
        'customizations',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'customizations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->status)) {
                $item->status = self::STATUS_PENDING;
            }
        });
    }

    public function kitchenOrder(): BelongsTo
    {
        return $this->belongsTo(KitchenOrder::class, 'id_kitchen_order', 'id_kitchen_order');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'id_order_item', 'id_order_item');
    }

    public function startProcessing(): bool
    {
        $this->status = self::STATUS_IN_PROGRESS;
        return $this->save();
    }

    public function complete(): bool
    {
        $this->status = self::STATUS_COMPLETED;
        return $this->save();
    }
}
