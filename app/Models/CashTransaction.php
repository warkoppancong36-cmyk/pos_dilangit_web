<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cash_transactions';
    protected $primaryKey = 'id_cash_transaction';

    protected $fillable = [
        'id_cash_register',
        'id_user',
        'id_shift',
        'id_order',
        'type',
        'source',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'notes',
        'reference_number',
        'metadata',
        'transaction_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
        'transaction_date' => 'datetime'
    ];

    // Relationships
    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'id_cash_register');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'id_shift');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order');
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('transaction_date', today());
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySource($query, $source)
    {
        return $query->where('source', $source);
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getTypeTextAttribute(): string
    {
        return $this->type === 'in' ? 'Kas Masuk' : 'Kas Keluar';
    }
}
