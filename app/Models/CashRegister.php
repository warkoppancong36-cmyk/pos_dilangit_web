<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cash_registers';
    protected $primaryKey = 'id_cash_register';

    protected $fillable = [
        'register_name',
        'register_code',
        'location',
        'active',
        'current_cash_balance',
        'supported_payment_methods',
        'hardware_config',
        'description',
        'last_activity',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'active' => 'boolean',
        'current_cash_balance' => 'decimal:2',
        'supported_payment_methods' => 'array',
        'hardware_config' => 'array',
        'last_activity' => 'datetime'
    ];

    // Relationships
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'id_cash_register');
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class, 'id_cash_register');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    // Methods
    public function updateBalance(float $amount, string $type = 'in'): void
    {
        if ($type === 'in') {
            $this->current_cash_balance += $amount;
        } else {
            $this->current_cash_balance -= $amount;
        }
        
        $this->last_activity = now();
        $this->save();
    }

    public function getTodayTransactions()
    {
        return $this->cashTransactions()
            ->today()
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    public function getTodaySales(): float
    {
        return $this->cashTransactions()
            ->today()
            ->byType('in')
            ->bySource('sale')
            ->sum('amount');
    }

    public function getTodayExpenses(): float
    {
        return $this->cashTransactions()
            ->today()
            ->byType('out')
            ->where('source', '!=', 'sale')
            ->sum('amount');
    }
}
