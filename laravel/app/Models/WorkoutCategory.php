<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkoutCategory extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'icon',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function exercises(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Exercise::class, 'workout_category_id');
    }
}
