<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ppn extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'ppn';

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'id_ppn';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'nominal',
        'description',
        'active',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'active' => 'boolean',
        'nominal' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Get the user who created this PPN.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this PPN.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted this PPN.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Scope a query to only include active PPNs.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include inactive PPNs.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Get formatted nominal with percentage.
     */
    public function getFormattedNominalAttribute()
    {
        return $this->nominal . '%';
    }

    /**
     * Get PPN rate as decimal (for calculations).
     */
    public function getRateAttribute()
    {
        return $this->nominal / 100;
    }
}
