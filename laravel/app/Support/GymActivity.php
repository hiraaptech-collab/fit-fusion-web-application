<?php
namespace App\Support;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Str;
class GymActivity {
    public static function record(string $action, $record = null): void {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action,
        'entity_type' => $record ? class_basename($record) : null,
        'entity_id' => $record?->id, 'ip_address' => request()->ip()]);
    }
    public static function notify(User $user, string $title, string $body): void {
        $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'gym.notice',
        'data' => ['title' => $title, 'body' => $body]]);
    }
    public static function cents(string $amount): int {
        // Validated decimal amounts are converted to integer cents for comparisons.
        return (int) round((float) $amount * 100);
    }
}
