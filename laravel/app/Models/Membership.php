<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    protected $fillable = [
        'user_id',
        'membership_plan_id',
        'plan_name',
        'duration_days',
        'agreed_price',
        'currency',
        'starts_on',
        'ends_on',
        'status',
        'cancelled_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'agreed_price' => 'decimal:2',
            'duration_days' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id')->withTrashed();
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class, 'membership_id');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNetPaidAttribute(): float {
        $paid = $this->payments->where('status', 'paid')->sum('amount');
        $refunded = $this->payments->flatMap(fn($payment) => $payment->refunds)->where('status', 'completed')->sum('amount');
        return round((float)$paid - (float)$refunded, 2);
    }
    public function getEffectiveStatusAttribute(): string {
        if ($this->status === 'cancelled') return 'cancelled';
        if ($this->ends_on->isBefore(\App\Support\GymClock::today())) return 'expired';
        return $this->starts_on->isAfter(\App\Support\GymClock::today()) ? 'pending' : 'active';
    }

    protected static function booted(): void
    {
        // Financial history is retained. Cancel/void entries instead of deleting them.
        static::deleting(function () {
            throw new \LogicException('Historical membership and payment records cannot be deleted.');
        });
    }
}

