<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'duration_days',
        'price',
        'currency',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function memberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Membership::class, 'membership_plan_id');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
