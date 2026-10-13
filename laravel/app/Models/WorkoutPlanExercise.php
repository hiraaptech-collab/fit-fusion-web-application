<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutPlanExercise extends Model
{
    protected $fillable = [
        'workout_plan_id',
        'exercise_id',
        'sets',
        'reps',
        'sort_order',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class, 'workout_plan_id');
    }

    public function exercise(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }
}
