<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'specialties',
        'qualifications',
        'hired_on',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'hired_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schedules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffSchedule::class, 'trainer_id');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffAttendance::class, 'trainer_id');
    }
}
