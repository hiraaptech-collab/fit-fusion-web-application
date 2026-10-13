<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use App\Rules\PasswordLength;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateOwner extends Command
{
    protected $signature = 'fitfusion:create-owner {--name=} {--email=}';
    protected $description = 'Create the initial Owner locally, with hidden password entry';

    public function handle(): int
    {
        if (User::withTrashed()->where('role', 'owner')->exists()) {
            $this->error('An Owner already exists. This command will not replace that account.');
            return self::FAILURE;
        }
        $name = $this->option('name') ?: $this->ask('Owner name');
        $email = mb_strtolower(trim($this->option('email') ?: $this->ask('Owner email')));
        // false disables visible-input fallback if the terminal cannot hide passwords.
        $password = $this->secret('Password (12+ characters, upper/lowercase and a number)', false);
        $confirmation = $this->secret('Confirm password', false);
        $validator = Validator::make([
            'name' => $name, 'email' => $email,
            'password' => $password, 'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'max:72', new PasswordLength(), Password::min(12)->mixedCase()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }
        DB::transaction(function () use ($name, $email, $password) {
            $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
            $user->role = 'owner';
            $user->is_active = true;
            $user->save();
            AuditLog::create(['user_id' => $user->id, 'action' => 'owner.created', 'entity_type' => 'user', 'entity_id' => $user->id]);
        });
        $this->info('Owner account created. Sign in through /login.');
        return self::SUCCESS;
    }
}
