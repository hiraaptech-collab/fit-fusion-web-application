<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'membership_id',
        'receipt_number',
        'amount',
        'currency',
        'method',
        'status',
        'reference',
        'paid_at',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function membership(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Membership::class, 'membership_id');
    }

    public function refunds(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Refund::class, 'payment_id');
    }

    public function recordedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    protected static function booted(): void
    {
        // Financial history is retained. Cancel/void entries instead of deleting them.
        static::deleting(function () {
            throw new \LogicException('Historical membership and payment records cannot be deleted.');
        });
    }
}
