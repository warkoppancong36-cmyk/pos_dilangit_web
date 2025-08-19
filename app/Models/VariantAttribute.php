<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VariantAttribute extends Model
{
    use HasFactory;

    protected $table = 'variant_attributes';
    protected $primaryKey = 'id_variant_attribute';

    protected $fillable = [
        'id_product',
        'attribute_name',
        'attribute_type',
        'attribute_values',
        'is_required',
        'sort_order',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attribute_values' => 'array',
        'is_required' => 'boolean',
        'active' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'values_count',
        'display_values',
    ];

    /**
     * Relationship dengan Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product', 'id_product');
    }

    /**
     * Relationship dengan User Creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship dengan User Updater
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor untuk values count
     */
    public function getValuesCountAttribute(): int
    {
        return count($this->attribute_values ?? []);
    }

    /**
     * Accessor untuk display values
     */
    public function getDisplayValuesAttribute(): string
    {
        $values = $this->attribute_values ?? [];
        
        if (empty($values)) {
            return 'Tidak ada nilai';
        }

        if (count($values) <= 3) {
            return implode(', ', $values);
        }

        return implode(', ', array_slice($values, 0, 3)) . ' ... +' . (count($values) - 3) . ' lainnya';
    }

    /**
     * Scope untuk active attributes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope untuk required attributes
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope untuk filter berdasarkan product
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('id_product', $productId);
    }

    /**
     * Scope untuk filter berdasarkan attribute type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('attribute_type', $type);
    }

    /**
     * Scope untuk ordering berdasarkan sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('attribute_name', 'asc');
    }
}
