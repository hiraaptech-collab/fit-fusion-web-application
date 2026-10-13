<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DietPlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'icon',
        'image',
        'calories',
        'breakfast',
        'lunch',
        'dinner',
        'snacks',
        'benefits',
        'tips',
        'notes',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'tips' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClientPlanAssignment::class, 'diet_plan_id');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
