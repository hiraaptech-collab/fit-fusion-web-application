<?php

namespace App\Models;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    // Role and activation cannot be changed through mass assignment.
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function hasGymPermission(string $permission): bool
    {
        if (! $this->is_active || $this->trashed()) {
            return false;
        }
        if ($this->role === 'owner') {
            return true;
        }
        return $this->role === 'manager'
            && array_key_exists($permission, config('gym.manager_permissions'))
            && ($this->relationLoaded('managerPermissions')
                ? $this->managerPermissions->contains('permission', $permission)
                : $this->managerPermissions()->where('permission', $permission)->exists());
    }

    public function sendPasswordResetNotification($token): void
    {
        if (config('gym.password_reset_delivery') !== 'preview') {
            if (in_array(config('mail.default'), ['log', 'array'], true)) {
                throw new \RuntimeException('Configure a delivery mailer for password reset.');
            }
            $this->notify(new ResetPassword($token));
            return;
        }
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('Configure SMTP before enabling password reset outside local development.');
        }
        // Dot filenames also block previews through the original Express static server.
        $url = rtrim(config('app.url'), '/').route('password.reset', [
            'token' => $token, 'email' => $this->email,
        ], false);
        $saved = Storage::disk('local')->put('.password-resets/.'.Str::uuid().'.json', json_encode([
            'to' => $this->email,
            'subject' => 'Reset your FitFusion password',
            'url' => $url,
            'expires_in_minutes' => config('auth.passwords.users.expire'),
            'created_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        if (! $saved) {
            throw new \RuntimeException('Could not store the local reset preview.');
        }
    }

    public function clientProfile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function managerPermissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ManagerPermission::class, 'manager_id');
    }

    public function memberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function planAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClientPlanAssignment::class);
    }

    public function fitnessProgress(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FitnessProgress::class);
    }

    public function sentMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function inquiries(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function aiConversations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AiConversation::class);
    }

    public function reviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ClientReview::class);
    }

    public function auditLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}


