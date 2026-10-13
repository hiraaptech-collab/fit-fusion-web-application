<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientPlanAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'workout_plan_id',
        'diet_plan_id',
        'assigned_by',
        'starts_on',
        'ends_on',
        'notes',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function workoutPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class, 'workout_plan_id')->withTrashed();
    }

    public function dietPlan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DietPlan::class, 'diet_plan_id')->withTrashed();
    }

    public function assignedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

