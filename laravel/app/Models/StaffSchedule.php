<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffSchedule extends Model
{
    protected $fillable = [
        'trainer_id',
        'worker_id',
        'starts_at',
        'ends_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function trainer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Trainer::class, 'trainer_id')->withTrashed();
    }

    public function worker(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Worker::class, 'worker_id')->withTrashed();
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StaffAttendance::class, 'staff_schedule_id');
    }
}
