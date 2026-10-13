<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'description',
        'category',
        'amount',
        'currency',
        'incurred_on',
        'reference',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'incurred_on' => 'date',
        ];
    }

    public function recordedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
