<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function planExercises(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutPlanExercise::class, 'workout_plan_id');
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClientPlanAssignment::class, 'workout_plan_id');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
