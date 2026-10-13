<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientReview extends Model
{
    protected $fillable = ['display_name', 'rating', 'message', 'review_hash', 'status', 'reviewed_by', 'reviewed_at'];
    protected $hidden = ['user_id', 'review_hash', 'reviewed_by'];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'reviewed_at' => 'datetime'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public static function messageHash(string $message): string
    {
        // Whitespace and capitalization changes do not bypass duplicate prevention.
        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', trim($message)));
        return hash('sha256', $normalized);
    }
}
