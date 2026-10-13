<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    protected $fillable = [
        'trainer_id',
        'worker_id',
        'staff_schedule_id',
        'attendance_date',
        'status',
        'checked_in_at',
        'checked_out_at',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
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

    public function schedule(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(StaffSchedule::class, 'staff_schedule_id');
    }

    public function recordedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
