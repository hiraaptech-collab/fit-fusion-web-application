<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workout_category_id',
        'slug',
        'name',
        'sets',
        'reps',
        'difficulty',
        'calories',
        'muscle',
        'tips',
        'image',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(WorkoutCategory::class, 'workout_category_id');
    }

    public function planExercises(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkoutPlanExercise::class, 'exercise_id');
    }
}
