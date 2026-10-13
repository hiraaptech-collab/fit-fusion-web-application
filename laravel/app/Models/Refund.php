<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = [
        'payment_id',
        'reference',
        'amount',
        'currency',
        'reason',
        'status',
        'refunded_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_at' => 'datetime',
        ];
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
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
