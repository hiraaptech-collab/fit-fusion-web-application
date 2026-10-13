<?php

// Small, local-only cleanup tool for accounts created by verify-auth-browser.cjs.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

$email = $argv[1] ?? '';
$action = $argv[2] ?? 'cleanup';
if (!app()->environment('local') || DB::connection()->getDatabaseName() !== 'fitfusion_db'
    || !preg_match('/^browser-[a-f0-9-]+@example\\.test$/', $email)) {
    fwrite(STDERR, "Fixture scope check failed.\n");
    exit(1);
}
$user = User::where('email', $email)->where('name', 'Browser Verification')->where('role', 'client')->first();
if (!$user) {
    echo json_encode(['found' => false]), PHP_EOL;
    exit(0);
}
if ($action === 'deactivate' || $action === 'activate') {
    $user->is_active = $action === 'activate';
    $user->save();
    echo json_encode(['updated' => true]), PHP_EOL;
    exit(0);
}
if ($action !== 'cleanup' || $user->memberships()->exists() || $user->payments()->exists()) {
    fwrite(STDERR, "Fixture action rejected.\n");
    exit(1);
}
DB::transaction(function () use ($user) {
    DB::table('password_reset_tokens')->where('email', $user->email)->delete();
    DB::table('audit_logs')->where('user_id', $user->id)->delete();
    $user->forceDelete();
});
foreach (Storage::disk('local')->files('.password-resets') as $path) {
    $preview = json_decode(Storage::disk('local')->get($path), true);
    if (($preview['to'] ?? null) === $email) {
        Storage::disk('local')->delete($path);
    }
}
echo json_encode(['cleanedUp' => true]), PHP_EOL;
