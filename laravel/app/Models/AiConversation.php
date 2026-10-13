<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AiConversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'consented_at',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiMessage::class, 'ai_conversation_id');
    }
}
