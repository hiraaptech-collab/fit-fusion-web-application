<?php

// Run locally: php tests/verify-database-auth.php
// Real MariaDB tests run inside a transaction and roll back all test accounts.
putenv('APP_ENV=testing');
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=array');
foreach (['APP_ENV' => 'testing', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $key => $value) {
    $_ENV[$key] = $_SERVER[$key] = $value;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Console\Commands\CreateOwner;
use App\Models\AuditLog;
use App\Models\ClientPlanAssignment;
use App\Models\DietPlan;
use App\Models\Exercise;
use App\Models\FitnessProgress;
use App\Models\GymSetting;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\StaffSchedule;
use App\Models\Trainer;
use App\Models\User;
use App\Models\Worker;
use App\Models\WorkoutCategory;
use App\Models\WorkoutPlan;
use App\Models\WorkoutPlanExercise;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

$checks = [];
$reportPath = storage_path('app/private/phase4-4-verification/backend-tests.json');
$previewRoot = storage_path('app/private/phase4-4-verification/test-mail');
config(['filesystems.disks.local.root' => $previewRoot, 'gym.password_reset_delivery' => 'preview']);
Storage::forgetDisk('local');
$baselineUsers = User::withTrashed()->count();
DB::beginTransaction();

function check(bool $condition, string $name): void
{
    global $checks;
    if (! $condition) {
        throw new RuntimeException('Check failed: '.$name);
    }
    $checks[] = $name;
}

function httpRequest(string $method, string $uri, array $data = [], ?User $user = null, string $ip = '192.0.2.44'): array
{
    global $app;
    // Each synthetic request starts a fresh session; browser cookie continuity is tested separately.
    $app['session']->forgetDrivers();
    $app->forgetInstance('session.store');
    Auth::forgetGuards();
    if ($user) {
        Auth::guard()->setUser($user->fresh());
    }
    $request = Request::create($uri, $method, $data, [], [], [
        'HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => $ip,
    ]);
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return [
        'status' => $response->getStatusCode(),
        'body' => json_decode($response->getContent(), true),
        'text' => $response->getContent(),
        'location' => $response->headers->get('Location'),
    ];
}

function rejects(callable $operation, string $name): void
{
    try {
        $operation();
    } catch (QueryException | LogicException $error) {
        check(true, $name);
        return;
    }
    check(false, $name);
}

function fixtureUser(string $role, string $suffix, string $password): User
{
    $user = new User(['name' => 'Verification '.$suffix, 'email' => $suffix.'-'.Str::uuid().'@example.test', 'password' => $password]);
    $user->role = $role;
    $user->is_active = true;
    $user->save();
    return $user;
}

try {
    check((DB::connection()->getDatabaseName() === 'fitfusion_db' || (app()->environment('testing') && str_starts_with(DB::connection()->getDatabaseName(),'fitfusion_audit_'))), 'Connected to fitfusion_db');
    check(DB::connection()->getDriverName() === 'mariadb', 'Native Laravel MariaDB driver');
    $tables = DB::select("SELECT TABLE_NAME AS name, ENGINE AS engine FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    check(count($tables) === 35, 'All 34 original tables and the additive review table present');
    foreach ($tables as $table) {
        check($table->engine === 'InnoDB', 'InnoDB table '.$table->name);
        $primary = DB::selectOne("SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'PRIMARY KEY'", [$table->name]);
        check((int) $primary->total === 1, 'Primary key '.$table->name);
    }
    $foreignKeys = DB::select("SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
    check(count($foreignKeys) >= 40, 'Foreign keys installed');
    $columns = DB::select("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND (COLUMN_NAME LIKE '%tenant%' OR COLUMN_NAME = 'gym_id')");
    check(count($columns) === 0, 'Single gym schema without tenancy');
    rejects(fn () => DB::select('SELECT User FROM mysql.user'), 'Application user cannot read database account records');

    $counts = [WorkoutCategory::count(), Exercise::count(), WorkoutPlan::count(), WorkoutPlanExercise::count(), DietPlan::count(), GymSetting::count()];
    $app->make(Database\Seeders\DatabaseSeeder::class)->run();
    $app->make(Database\Seeders\DatabaseSeeder::class)->run();
    check($counts === [WorkoutCategory::count(), Exercise::count(), WorkoutPlan::count(), WorkoutPlanExercise::count(), DietPlan::count(), GymSetting::count()], 'Seeder runs twice without duplicates');
    check($counts === [7, 57, 7, 57, 8, 1], 'Imported 7 categories, 57 exercises, 7 plans, 57 links and 8 diets');
    $workouts = json_decode(file_get_contents(public_path('data/workouts.json')), true, 512, JSON_THROW_ON_ERROR);
    foreach ($workouts['categories'] as $categoryData) {
        $category = WorkoutCategory::where('slug', $categoryData['id'])->firstOrFail();
        check($category->title === $categoryData['title'] && $category->description === $categoryData['description'], 'Workout category fidelity '.$categoryData['id']);
        foreach ($categoryData['exercises'] as $exerciseData) {
            $exercise = Exercise::where('workout_category_id', $category->id)->where('slug', Str::slug($exerciseData['name']))->firstOrFail();
            foreach ($exerciseData as $key => $value) {
                check($exercise->$key === $value, 'Exercise field fidelity '.$categoryData['id'].'/'.$exerciseData['name'].'/'.$key);
            }
        }
    }
    $diets = json_decode(file_get_contents(public_path('data/diet-plans.json')), true, 512, JSON_THROW_ON_ERROR);
    foreach ($diets['diets'] as $dietData) {
        $diet = DietPlan::where('slug', $dietData['id'])->firstOrFail();
        foreach ($dietData as $key => $value) {
            if ($key !== 'id') {
                check($diet->$key === $value, 'Diet field fidelity '.$dietData['id'].'/'.$key);
            }
        }
    }

    $password = 'Aa1!'.bin2hex(random_bytes(12));
    $email = 'registration-'.Str::uuid().'@example.test';
    $registration = ['name' => 'Client Verification', 'email' => $email, 'password' => $password, 'password_confirmation' => $password];
    $result = httpRequest('POST', '/register', $registration);
    check($result['status'] === 302 && str_ends_with($result['location'], '/dashboard'), 'Client registration redirects to dashboard');
    $client = User::where('email', $email)->firstOrFail();
    check($client->role === 'client' && $client->is_active, 'Registration creates an active CLIENT only');
    check($client->clientProfile()->exists(), 'Registration creates client profile');
    check(Hash::check($password, $client->password) && $client->password !== $password, 'Password securely hashed');
    check(! str_contains(json_encode($client), $client->password) && ! str_contains(json_encode($client), 'remember_token'), 'User serialization hides password and token');
    check(httpRequest('POST', '/register', $registration, null, '192.0.2.45')['status'] === 422, 'Duplicate email rejected');
    foreach (['role' => 'owner', 'is_active' => false, 'permissions' => ['finance.view']] as $field => $value) {
        check(httpRequest('POST', '/register', [...$registration, 'email' => Str::uuid().'@example.test', $field => $value], null, '192.0.2.46')['status'] === 422, 'Registration privilege injection rejected '.$field);
    }
    check(httpRequest('POST', '/register', [...$registration, 'email' => 'invalid', 'password' => 'short', 'password_confirmation' => 'different'], null, '192.0.2.47')['status'] === 422, 'Invalid registration rejected');
    $unicodePassword = str_repeat('ÃƒÂ©', 50).'Aa1';
    check(httpRequest('POST', '/register', [...$registration, 'email' => Str::uuid().'@example.test', 'password' => $unicodePassword, 'password_confirmation' => $unicodePassword], null, '192.0.2.48')['status'] === 422, 'Unicode passwords cannot exceed bcrypt byte limit');
    check(httpRequest('POST', '/login', ['email' => strtoupper($email), 'password' => $password])['status'] === 302, 'Client login and email normalization');
    check(User::find($client->id)->last_login_at !== null, 'Login timestamp saved');
    check(httpRequest('GET', '/account', [], $client)['status'] === 200, 'Authenticated account page');
    check(httpRequest('GET', '/account')['status'] === 401, 'Account rejects anonymous JSON request');
    check(httpRequest('GET', '/client/profile', [], $client)['body']['profile']['user_id'] === $client->id, 'Client reads their own profile');
    check(httpRequest('POST', '/logout', [], $client)['status'] === 302, 'Logout route succeeds');
    check(httpRequest('POST', '/login', ['email' => $email, 'password' => 'IncorrectPassword123'])['status'] === 422, 'Wrong password rejected');

    $client->is_active = false;
    $client->save();
    check(httpRequest('POST', '/login', ['email' => $email, 'password' => $password])['status'] === 422, 'Inactive account cannot log in');
    check(httpRequest('GET', '/account', [], $client)['status'] === 403, 'Inactive existing session rejected');
    $client->is_active = true;
    $client->save();
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $limited = httpRequest('POST', '/login', ['email' => $email, 'password' => 'IncorrectPassword123'], null, '192.0.2.80');
    }
    check(str_contains($limited['text'], 'Too many login attempts'), 'Per-email login throttling');
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $limited = httpRequest('POST', '/register', [], null, '192.0.2.81');
    }
    check($limited['status'] === 429, 'Registration IP throttling');

    check(isset(Artisan::all()['fitfusion:create-owner']), 'Owner Artisan command registered');
    $ownerEmail = 'owner-command-'.Str::uuid().'@example.test';
    if (! User::withTrashed()->where('role', 'owner')->exists()) {
        $command = new class($password) extends CreateOwner {
            private string $testPassword;
            public function __construct(string $password) { parent::__construct(); $this->testPassword = $password; }
            public function secret($question, $fallback = true) { return $this->testPassword; }
        };
        $command->setLaravel($app);
        $output = new BufferedOutput();
        check($command->run(new ArrayInput(['--name' => 'Owner Verification', '--email' => $ownerEmail]), $output) === 0, 'Owner command creates account');
        check(! str_contains($output->fetch(), $password), 'Owner command does not print password');
        $owner = User::where('email', $ownerEmail)->firstOrFail();
        check($owner->role === 'owner' && Hash::check($password, $owner->password), 'Owner role and password hashing');
        check($command->run(new ArrayInput(['--name' => 'Another Owner', '--email' => 'another-'.Str::uuid().'@example.test']), new BufferedOutput()) === 1, 'Owner command refuses to replace existing Owner');
    } else {
        $owner = User::where('role', 'owner')->firstOrFail();
        $command = $app->make(CreateOwner::class);
        $command->setLaravel($app);
        check($command->run(new ArrayInput([]), new BufferedOutput()) === 1, 'Owner command refuses to replace the existing real Owner');
    }
    $managerData = ['name' => 'Manager Verification', 'email' => 'manager-'.Str::uuid().'@example.test', 'password' => $password, 'password_confirmation' => $password, 'permissions' => ['trainers.manage']];
    check(httpRequest('POST', '/owner/managers', $managerData)['status'] === 401, 'Anonymous manager creation blocked');
    check(httpRequest('POST', '/owner/managers', $managerData, $client)['status'] === 403, 'Client manager creation blocked');
    $result = httpRequest('POST', '/owner/managers', $managerData, $owner);
    check($result['status'] === 201, 'Owner creates manager');
    $manager = User::where('email', $managerData['email'])->firstOrFail();
    check($manager->role === 'manager' && Hash::check($password, $manager->password), 'Manager role and password hashing');
    check(! str_contains($result['text'], 'password'), 'Manager response hides credentials');
    check(httpRequest('POST', '/owner/managers', [...$managerData, 'email' => Str::uuid().'@example.test'], $manager)['status'] === 403, 'Manager cannot create another manager');
    check(httpRequest('GET', '/client/profile', [], $manager)['status'] === 403, 'Manager cannot access client-only route');
    check(Gate::forUser($manager)->allows('trainers.manage'), 'Manager approved permission allowed');
    check(Gate::forUser($manager)->denies('finance.view'), 'Manager unapproved finance denied');
    check(Gate::forUser($client)->denies('trainers.manage'), 'Client management permissions denied');
    check(Gate::forUser($owner)->allows('finance.manage'), 'Owner full management permissions');
    $permissionUri = '/owner/managers/'.$manager->id.'/permissions';
    check(httpRequest('PUT', $permissionUri, ['permissions' => ['finance.view']], $manager)['status'] === 403, 'Manager cannot grant self finance access');
    check(httpRequest('PUT', $permissionUri, ['permissions' => ['owner.manage']], $owner)['status'] === 422, 'Unknown permission rejected');
    check(httpRequest('PUT', $permissionUri, ['permissions' => ['finance.view']], $owner)['status'] === 200, 'Owner changes manager permissions');
    check(Gate::forUser($manager)->allows('finance.view') && Gate::forUser($manager)->denies('trainers.manage'), 'Permission replacement and revocation apply immediately');
    check(httpRequest('PUT', '/owner/managers/'.$owner->id.'/permissions', ['permissions' => []], $owner)['status'] === 404, 'Owner account cannot be targeted as manager');

    // Password broker, private preview, hashing, expiry and one-use behavior.
    $result = httpRequest('POST', '/forgot-password', ['email' => $email], null, '192.0.2.90');
    check($result['status'] === 302, 'Forgot password route');
    $previews = Storage::disk('local')->files('.password-resets');
    check(count($previews) === 1, 'Reset preview stored privately');
    check(str_starts_with(basename($previews[0]), '.'), 'Reset preview filename is protected from Express static serving');
    $preview = json_decode(Storage::disk('local')->get($previews[0]), true, 512, JSON_THROW_ON_ERROR);
    $resetToken = basename(parse_url($preview['url'], PHP_URL_PATH));
    check(str_starts_with($preview['url'], config('app.url').'/reset-password/'), 'Reset link uses trusted APP_URL');
    $storedToken = DB::table('password_reset_tokens')->where('email', $email)->value('token');
    check($storedToken !== $resetToken && Hash::check($resetToken, $storedToken), 'Reset token hashed in database');
    httpRequest('POST', '/forgot-password', ['email' => $email], null, '192.0.2.91');
    check(count(Storage::disk('local')->files('.password-resets')) === 1, 'Password broker throttles repeat email');
    httpRequest('POST', '/forgot-password', ['email' => 'unknown-'.Str::uuid().'@example.test'], null, '192.0.2.92');
    check(count(Storage::disk('local')->files('.password-resets')) === 1, 'Unknown email creates no reset token or preview');
    $resetPassword = 'Bb2!'.bin2hex(random_bytes(12));
    $resetData = ['email' => $email, 'token' => $resetToken, 'password' => $resetPassword, 'password_confirmation' => $resetPassword];
    check(httpRequest('POST', '/reset-password', [...$resetData, 'token' => 'invalid-token'])['status'] === 302, 'Invalid reset rejected safely');
    check(Hash::check($password, User::find($client->id)->password), 'Invalid reset leaves password unchanged');
    check(httpRequest('POST', '/reset-password', $resetData)['status'] === 302, 'Valid password reset succeeds');
    check(Hash::check($resetPassword, User::find($client->id)->password), 'New reset password hashed');
    check(! DB::table('password_reset_tokens')->where('email', $email)->exists(), 'Used reset token removed');
    httpRequest('POST', '/reset-password', $resetData);
    check(! DB::table('password_reset_tokens')->where('email', $email)->exists(), 'Reset token cannot be reused');
    $expiredToken = Password::broker()->createToken($client);
    DB::table('password_reset_tokens')->where('email', $email)->update(['created_at' => now()->subMinutes(61)]);
    httpRequest('POST', '/reset-password', [...$resetData, 'token' => $expiredToken, 'password' => $password, 'password_confirmation' => $password]);
    check(Hash::check($resetPassword, User::find($client->id)->password), 'Expired reset token rejected');
    check(httpRequest('POST', '/login', ['email' => $email, 'password' => $resetPassword], null, '192.0.2.93')['status'] === 302, 'Login with reset password');
    check(httpRequest('POST', '/login', ['email' => $email, 'password' => $password], null, '192.0.2.94')['status'] === 422, 'Old password rejected after reset');

    // Exercise every declared model relationship against real tables.
    $relationshipCount = 0;
    foreach (glob(app_path('Models/*.php')) as $file) {
        $class = 'App\\Models\\'.basename($file, '.php');
        $model = new $class();
        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $type = (string) $method->getReturnType();
            if ($method->getNumberOfRequiredParameters() === 0 && str_contains($type, 'Illuminate\\Database\\Eloquent\\Relations\\')) {
                $relation = $model->{$method->name}();
                check($relation instanceof Relation, 'Eloquent relationship '.$class.'::'.$method->name);
                $relation->get();
                $relationshipCount++;
            }
        }
    }
    check($relationshipCount >= 60, 'At least 60 relationships queried successfully');

    $trainer = Trainer::create(['name' => 'Trainer Verification']);
    $worker = Worker::create(['name' => 'Worker Verification', 'job_title' => 'Reception']);
    StaffSchedule::create(['trainer_id' => $trainer->id, 'starts_at' => '2026-10-20 09:00:00', 'ends_at' => '2026-10-20 10:00:00']);
    rejects(fn () => StaffSchedule::create(['trainer_id' => $trainer->id, 'worker_id' => $worker->id, 'starts_at' => '2026-10-20 09:00:00', 'ends_at' => '2026-10-20 10:00:00']), 'Schedule rejects two staff owners');
    rejects(fn () => StaffSchedule::create(['worker_id' => $worker->id, 'starts_at' => '2026-10-20 10:00:00', 'ends_at' => '2026-10-20 09:00:00']), 'Schedule rejects reversed dates');
    rejects(fn () => GymSetting::create(['id' => 2, 'name' => 'Another Gym']), 'Second gym settings row rejected');
    rejects(fn () => ClientPlanAssignment::create(['user_id' => $client->id, 'workout_plan_id' => WorkoutPlan::first()->id, 'diet_plan_id' => DietPlan::first()->id, 'assigned_by' => $owner->id, 'starts_on' => '2026-10-20']), 'Assignment rejects two plans');
    rejects(fn () => FitnessProgress::create(['user_id' => $client->id, 'recorded_on' => '2026-10-20', 'body_fat_percent' => 101]), 'Fitness percentage bounds enforced');
    $plan = MembershipPlan::create(['name' => 'Original terms', 'slug' => 'verification-'.Str::uuid(), 'duration_days' => 30, 'price' => '1500.00', 'currency' => 'PKR']);
    $membership = Membership::create(['user_id' => $client->id, 'membership_plan_id' => $plan->id, 'plan_name' => $plan->name, 'duration_days' => $plan->duration_days, 'agreed_price' => $plan->price, 'currency' => $plan->currency, 'starts_on' => '2026-10-10', 'ends_on' => '2026-11-09']);
    $payment = Payment::create(['user_id' => $client->id, 'membership_id' => $membership->id, 'receipt_number' => Str::uuid(), 'amount' => '1500.00', 'method' => 'cash', 'status' => 'paid']);
    $refund = Refund::create(['payment_id' => $payment->id, 'reference' => Str::uuid(), 'amount' => '100.00', 'reason' => 'Verification only', 'status' => 'completed']);
    rejects(fn () => DB::table('payments')->where('id', $payment->id)->delete(), 'Refund history prevents payment hard deletion');
    rejects(fn () => $refund->delete(), 'Refund model prevents historical deletion');
    $plan->update(['name' => 'Revised terms', 'price' => '2000.00']);
    check($membership->fresh()->plan_name === 'Original terms' && $membership->fresh()->agreed_price === '1500.00', 'Membership snapshots preserved after plan change');
    rejects(fn () => DB::table('membership_plans')->where('id', $plan->id)->delete(), 'Referenced membership plan cannot be hard deleted');
    rejects(fn () => DB::table('users')->where('id', $client->id)->delete(), 'Client financial history prevents hard deletion');
    rejects(fn () => $membership->delete(), 'Membership model prevents historical deletion');
    rejects(fn () => $payment->delete(), 'Payment model prevents historical deletion');
    rejects(fn () => Payment::create(['user_id' => $client->id, 'receipt_number' => Str::uuid(), 'amount' => '-1.00', 'method' => 'cash']), 'Negative payment rejected');
    $plan->delete();
    check($membership->fresh()->plan?->id === $plan->id, 'Archived membership plan remains available to history');
    $client->delete();
    check($membership->fresh()->user?->id === $client->id, 'Archived client remains available to membership history');
    check($payment->fresh()->user?->id === $client->id, 'Archived client remains available to payment history');
    check(AuditLog::where('action', 'auth.register')->exists() && AuditLog::where('action', 'manager.created')->exists(), 'Authentication and manager audit records');

    foreach (['/', '/index.html', '/about.html', '/dietplans.html', '/workouts.html', '/BMI.html', '/fusion-ai.html', '/contact.html'] as $uri) {
        check(httpRequest('GET', $uri)['status'] === 200, 'Public page '.$uri);
    }
    $report = ['passed' => true, 'checks' => count($checks), 'tables' => count($tables), 'foreignKeys' => count($foreignKeys), 'relationshipsQueried' => $relationshipCount, 'importCounts' => $counts, 'testAccountsRolledBack' => true, 'smtpDeliveryTested' => false, 'checksExecuted' => $checks];
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo json_encode(array_diff_key($report, ['checksExecuted' => true]), JSON_PRETTY_PRINT), PHP_EOL;
} catch (Throwable $error) {
    $report = ['passed' => false, 'checks' => count($checks), 'failure' => $error instanceof RuntimeException ? $error->getMessage() : get_class($error), 'checksExecuted' => $checks];
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo json_encode(array_diff_key($report, ['checksExecuted' => true]), JSON_PRETTY_PRINT), PHP_EOL;
    $exitCode = 1;
} finally {
    DB::rollBack();
    foreach (Storage::disk('local')->files('.password-resets') as $path) {
        Storage::disk('local')->delete($path);
    }
    if (User::withTrashed()->count() !== $baselineUsers) {
        echo "Test account rollback verification failed.\n";
        $exitCode = 1;
    }
}
exit($exitCode ?? 0);



