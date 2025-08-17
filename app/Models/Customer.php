<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customers';
    protected $primaryKey = 'id_customer';

    protected $fillable = [
        'customer_code',
        'name',
        'email',
        'phone',
        'birth_date',
        'gender',
        'address',
        'city',
        'postal_code',
        'active',
        'total_visits',
        'total_spent',
        'last_visit',
        'preferences',
        'notes',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'active' => 'boolean',
        'total_visits' => 'integer',
        'total_spent' => 'decimal:2',
        'last_visit' => 'datetime',
        'preferences' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_total_spent',
        'age',
        'customer_status',
        'loyalty_level',
    ];

    /**
     * Boot method untuk auto-generate customer code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $customer->customer_code = $customer->generateCustomerCode();
            }
        });
    }

    /**
     * Generate unique customer code
     */
    public function generateCustomerCode(): string
    {
        $prefix = 'CUST';
        $date = now()->format('ymd');
        
        // Get last customer code for today
        $lastCustomer = static::whereDate('created_at', now())
            ->where('customer_code', 'like', $prefix . $date . '%')
            ->orderBy('customer_code', 'desc')
            ->first();

        if ($lastCustomer) {
            $lastNumber = (int) substr($lastCustomer->customer_code, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . $date . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'id_customer', 'id_customer');
    }

    public function loyaltyPoints(): HasMany
    {
        return $this->hasMany(CustomerLoyalty::class, 'id_customer', 'id_customer');
    }

    /**
     * Computed Attributes
     */
    public function getFormattedTotalSpentAttribute(): string
    {
        return 'Rp ' . number_format($this->total_spent, 0, ',', '.');
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }
        return $this->birth_date->diffInYears(now());
    }

    public function getCustomerStatusAttribute(): string
    {
        if (!$this->active) {
            return 'inactive';
        }

        if (!$this->last_visit) {
            return 'new';
        }

        $daysSinceLastVisit = $this->last_visit->diffInDays(now());
        
        if ($daysSinceLastVisit <= 7) {
            return 'active';
        } elseif ($daysSinceLastVisit <= 30) {
            return 'regular';
        } elseif ($daysSinceLastVisit <= 90) {
            return 'inactive';
        } else {
            return 'dormant';
        }
    }

    public function getLoyaltyLevelAttribute(): string
    {
        if ($this->total_spent >= 10000000) { // 10 juta
            return 'platinum';
        } elseif ($this->total_spent >= 5000000) { // 5 juta
            return 'gold';
        } elseif ($this->total_spent >= 1000000) { // 1 juta
            return 'silver';
        } elseif ($this->total_spent >= 100000) { // 100 ribu
            return 'bronze';
        } else {
            return 'basic';
        }
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('customer_code', 'like', "%{$search}%");
        });
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByLoyaltyLevel($query, $level)
    {
        switch ($level) {
            case 'platinum':
                return $query->where('total_spent', '>=', 10000000);
            case 'gold':
                return $query->whereBetween('total_spent', [5000000, 9999999]);
            case 'silver':
                return $query->whereBetween('total_spent', [1000000, 4999999]);
            case 'bronze':
                return $query->whereBetween('total_spent', [100000, 999999]);
            case 'basic':
                return $query->where('total_spent', '<', 100000);
            default:
                return $query;
        }
    }

    public function scopeRecentCustomers($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeFrequentCustomers($query, $minVisits = 5)
    {
        return $query->where('total_visits', '>=', $minVisits);
    }

    /**
     * Methods
     */
    public function incrementVisit(): bool
    {
        $this->total_visits++;
        $this->last_visit = now();
        return $this->save();
    }

    public function addSpending(float $amount): bool
    {
        $this->total_spent += $amount;
        return $this->save();
    }

    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address,
            $this->city,
            $this->postal_code
        ]);

        return implode(', ', $addressParts);
    }

    public function hasRecentActivity(int $days = 30): bool
    {
        return $this->last_visit && $this->last_visit->diffInDays(now()) <= $days;
    }
}
