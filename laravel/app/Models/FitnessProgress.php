<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FitnessProgress extends Model
{
    protected $table = 'fitness_progress';

    protected $fillable = [
        'user_id',
        'recorded_on',
        'weight_kg',
        'height_cm',
        'bmi',
        'body_fat_percent',
        'measurements',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_on' => 'date',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'bmi' => 'decimal:2',
            'body_fat_percent' => 'decimal:2',
            'measurements' => 'array',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
