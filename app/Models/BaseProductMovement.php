<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseProductMovement extends Model
{
    use HasFactory;

    protected $table = 'base_product_movements';
    protected $primaryKey = 'id_base_movement';

    protected $fillable = [
        'id_base_inventory',
        'movement_type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'reference_number',
        'notes',
        'movement_date',
        'created_by'
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'stock_before' => 'decimal:3',
        'stock_after' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'movement_date' => 'datetime'
    ];

    protected $appends = [
        'formatted_quantity',
        'formatted_total_cost',
        'movement_type_label'
    ];

    // Relationships
    public function baseInventory()
    {
        return $this->belongsTo(BaseProductInventory::class, 'id_base_inventory', 'id_base_inventory');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 3);
    }

    public function getFormattedTotalCostAttribute()
    {
        return 'Rp ' . number_format($this->total_cost, 0, ',', '.');
    }

    public function getMovementTypeLabelAttribute()
    {
        $labels = [
            'in' => 'Masuk',
            'out' => 'Keluar',
            'adjustment' => 'Penyesuaian',
            'transfer' => 'Transfer'
        ];

        return $labels[$this->movement_type] ?? ucfirst($this->movement_type);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }

    public function scopeByReference($query, $type, $id = null)
    {
        $query = $query->where('reference_type', $type);
        
        if ($id) {
            $query = $query->where('reference_id', $id);
        }
        
        return $query;
    }
}
