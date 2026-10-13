<?php
namespace App\Policies;
use App\Models\User;
use App\Models\AiConversation;
use App\Models\Message;
class ClientRecordPolicy {
    public function view(User $user, $record): bool {
        if ($record instanceof AiConversation) {
            return $user->role === 'client' && $record->user_id === $user->id;
        }
        if ($record instanceof Message) {
            return $record->sender_id === $user->id || $record->recipient_id === $user->id
            || $user->hasGymPermission('messages.manage');
        }
        return $record->user_id === $user->id && $user->role === 'client';
    }
    public function update(User $user, $record): bool {
        return $this->view($user, $record);
    }
}
