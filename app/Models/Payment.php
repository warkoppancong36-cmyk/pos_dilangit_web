<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';
    protected $primaryKey = 'id_payment';

    protected $fillable = [
        'id_order',
        'payment_number',
        'payment_method',
        'amount',
        'payment_bank',
        'cash_received',
        'change_amount',
        'reference_number',
        'status',
        'payment_details',
        'notes',
        'payment_date',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'payment_details' => 'array',
        'payment_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_amount',
        'payment_method_text',
        'status_text',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'id_order', 'id_order');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getPaymentMethodTextAttribute(): string
    {
        $methods = [
            'cash' => 'Tunai',
            'card' => 'Kartu',
            'digital_wallet' => 'E-Wallet',
            'bank_transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'other' => 'Lainnya'
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'pending' => 'Menunggu',
            'completed' => 'Berhasil',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    // Scopes
    public function scopeByMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_date)) {
                $payment->payment_date = now();
            }
            
            if (empty($payment->status)) {
                $payment->status = 'pending';
            }
        });

        static::saved(function ($payment) {
            // Update order payment status
            if ($payment->order) {
                $totalPaid = $payment->order->payments()->where('status', 'completed')->sum('amount');
                $orderTotal = $payment->order->total_amount;
                
                if ($totalPaid >= $orderTotal) {
                    // Order is fully paid, mark as ready or completed
                    if ($payment->order->status === 'pending') {
                        $payment->order->updateStatus('preparing');
                    }
                }
            }
        });
    }
}
