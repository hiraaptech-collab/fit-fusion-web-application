<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    protected $fillable = [
        'ai_conversation_id',
        'role',
        'content',
        'source',
    ];

    public function conversation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'ai_conversation_id');
    }
}
