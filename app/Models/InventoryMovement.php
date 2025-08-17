<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $table = 'inventory_movements';
    protected $primaryKey = 'id_movement';

    protected $fillable = [
        'id_inventory',
        'movement_type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'batch_number',
        'expiry_date',
        'notes',
        'created_by',
        'movement_date'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'movement_date' => 'datetime',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Relationships
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'id_inventory', 'id_inventory');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getFormattedQuantityAttribute()
    {
        return $this->quantity >= 0 ? '+' . $this->quantity : $this->quantity;
    }

    public function getFormattedCostAttribute()
    {
        return 'Rp ' . number_format($this->total_cost, 0, ',', '.');
    }

    public function getMovementTypeNameAttribute()
    {
        $types = [
            'stock_in' => 'Stock Masuk',
            'stock_out' => 'Stock Keluar',
            'adjustment' => 'Penyesuaian',
            'transfer' => 'Transfer',
            'return' => 'Retur',
            'damaged' => 'Rusak',
            'expired' => 'Kadaluarsa'
        ];

        return $types[$this->movement_type] ?? $this->movement_type;
    }

    // Scopes
    public function scopeStockIn($query)
    {
        return $query->where('movement_type', 'stock_in');
    }

    public function scopeStockOut($query)
    {
        return $query->where('movement_type', 'stock_out');
    }

    public function scopeAdjustments($query)
    {
        return $query->where('movement_type', 'adjustment');
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByMovementType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
