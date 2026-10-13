<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'fitness_goal',
        'fitness_data_consent_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'fitness_data_consent_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
