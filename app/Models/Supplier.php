<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'suppliers';
    protected $primaryKey = 'id_supplier';

    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'contact_person',
        'tax_number',
        'bank_name',
        'bank_account',
        'bank_account_name',
        'notes',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id', 'id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeSearch($query, $search)
    {
        if (!$search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('code', 'like', "%{$search}%")
              ->orWhere('contact_person', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('city', 'like', "%{$search}%");
        });
    }

    public function scopeByCity($query, $city)
    {
        if (!$city) return $query;
        return $query->where('city', $city);
    }

    public function scopeByProvince($query, $province)
    {
        if (!$province) return $query;
        return $query->where('province', $province);
    }

    /**
     * Accessors & Mutators
     */
    public function getPaymentTermsTextAttribute(): string
    {
        if (!$this->payment_terms) return 'Cash';
        
        $terms = $this->payment_terms;
        if (isset($terms['net_days'])) {
            return "Net {$terms['net_days']} days";
        }
        
        return 'Cash';
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->province,
            $this->postal_code
        ]);

        return implode(', ', $parts);
    }

    /**
     * Helper Methods
     */
    public function getTotalPurchases(): int
    {
        return $this->purchases()->count();
    }

    public function getTotalPurchaseAmount(): float
    {
        return $this->purchases()->sum('total_amount');
    }

    public function getLastPurchaseDate(): ?string
    {
        $lastPurchase = $this->purchases()->latest('purchase_date')->first();
        return $lastPurchase ? $lastPurchase->purchase_date : null;
    }

    /**
     * Generate unique supplier code
     */
    public static function generateCode(): string
    {
        $lastSupplier = static::withTrashed()->orderBy('id', 'desc')->first();
        $nextNumber = $lastSupplier ? $lastSupplier->id + 1 : 1;
        
        return 'SUP-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supplier) {
            if (!$supplier->code) {
                $supplier->code = static::generateCode();
            }
            
            if (auth()->id()) {
                $supplier->created_by = auth()->id();
            }
        });

        static::updating(function ($supplier) {
            if (auth()->id()) {
                $supplier->updated_by = auth()->id();
            }
        });

        static::deleting(function ($supplier) {
            if (auth()->id()) {
                $supplier->deleted_by = auth()->id();
                $supplier->save();
            }
        });
    }
}
