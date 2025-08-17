<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchases';
    protected $primaryKey = 'id_purchase';

    protected $fillable = [
        'purchase_number',
        'supplier_id',
        'purchase_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'notes',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id_supplier');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id', 'id_purchase');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOrdered($query)
    {
        return $query->where('status', 'ordered');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('purchase_date', now()->month)
                    ->whereYear('purchase_date', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('purchase_date', now()->toDateString());
    }

    // Accessors
    public function getStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Pending',
            'ordered' => 'Dipesan',
            'received' => 'Diterima',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan'
        ];

        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'ordered' => 'info',
            'received' => 'primary',
            'completed' => 'success',
            'cancelled' => 'error'
        ];

        return $colors[$this->status] ?? 'default';
    }

    public function getFormattedTotalAttribute()
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getFormattedPurchaseDateAttribute()
    {
        return $this->purchase_date ? $this->purchase_date->format('d/m/Y') : '';
    }

    public function getFormattedExpectedDeliveryDateAttribute()
    {
        return $this->expected_delivery_date ? $this->expected_delivery_date->format('d/m/Y') : '';
    }

    public function getFormattedActualDeliveryDateAttribute()
    {
        return $this->actual_delivery_date ? $this->actual_delivery_date->format('d/m/Y') : '';
    }

    // Mutators
    public function setPurchaseDateAttribute($value)
    {
        $this->attributes['purchase_date'] = $value ? date('Y-m-d', strtotime($value)) : null;
    }

    public function setExpectedDeliveryDateAttribute($value)
    {
        $this->attributes['expected_delivery_date'] = $value ? date('Y-m-d', strtotime($value)) : null;
    }

    public function setActualDeliveryDateAttribute($value)
    {
        $this->attributes['actual_delivery_date'] = $value ? date('Y-m-d', strtotime($value)) : null;
    }

    // Helper methods
    public function getTotalItems()
    {
        return $this->items()->sum('quantity');
    }

    public function getTotalItemTypes()
    {
        return $this->items()->count();
    }

    public function canBeEdited()
    {
        return in_array($this->status, ['pending', 'ordered']);
    }

    public function canBeDeleted()
    {
        return $this->status === 'pending';
    }

    public function canBeCancelled()
    {
        return in_array($this->status, ['pending', 'ordered']);
    }

    public function canBeReceived()
    {
        return $this->status === 'ordered';
    }

    public function canBeCompleted()
    {
        return $this->status === 'received';
    }
}
