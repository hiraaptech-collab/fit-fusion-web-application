<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GymSetting extends Model
{
    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'address',
        'timezone',
        'currency',
        'opening_hours',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
        ];
    }

    public function updatedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
