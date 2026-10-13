<?php
// Local navbar verification only. No production routes or credentials are changed.
require __DIR__.'/../vendor/autoload.php';

use App\Models\User;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$isServer = PHP_SAPI === 'cli-server';
if ($isServer) {
    // Static assets use the same public files as the normal Laravel server.
    $public = realpath(__DIR__.'/../public');
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = realpath($public.rawurldecode($path));
    if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR)
        && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
        return false;
    }
    if (($_SERVER['SERVER_NAME'] ?? '') !== '127.0.0.1'
        || ($_SERVER['SERVER_PORT'] ?? '') != 8001
        || !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)
        || ($_SERVER['REQUEST_METHOD'] === 'POST' && $path !== '/logout')) {
        http_response_code(403);
        exit;
    }
}
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(ConsoleKernel::class)->bootstrap();
if (!app()->environment('local') || DB::connection()->getDatabaseName() !== 'fitfusion_db') {
    throw new RuntimeException('Navbar verification is restricted to the local FitFusion database.');
}
// Separate test sessions cannot overwrite the real Owner browser session.
$sessionFolder = storage_path('framework/sessions/navbar-verification');
if (!is_dir($sessionFolder)) { mkdir($sessionFolder, 0700, true); }
config(['session.files' => $sessionFolder]);

if ($isServer) {
    // Roll back logout audit/remember-token writes, preserving all existing rows.
    DB::beginTransaction();
    try {
        $kernel = $app->make(HttpKernel::class);
        $request = Request::capture();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);
    } finally {
        DB::rollBack();
    }
    return;
}

$action = $argv[1] ?? 'snapshot';
if ($action === 'session') {
    // A signed-in fixture verifies the navbar without asking for the Owner password.
    $owner = User::where('role', 'owner')->where('is_active', true)->firstOrFail();
    $session = app('session')->driver();
    $session->start();
    Auth::login($owner);
    $session->save();
    $name = config('session.cookie');
    $prefix = CookieValuePrefix::create($name, app('encrypter')->getKey());
    $value = app('encrypter')->encrypt($prefix.$session->getId(), false);
    // Captured privately by the test runner; never written to a report or log.
    echo json_encode(['name' => $name, 'value' => $value]), PHP_EOL;
    exit;
}
if ($action !== 'snapshot') { throw new RuntimeException('Unknown verification action.'); }
$snapshot = [];
$tables = DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME');
foreach ($tables as $table) {
    $keys = DB::select("SELECT COLUMN_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY ORDINAL_POSITION", [$table->name]);
    $query = DB::table($table->name);
    foreach ($keys as $key) { $query->orderBy($key->name); }
    $snapshot[$table->name] = hash('sha256', json_encode($query->get()->all(), JSON_THROW_ON_ERROR));
}
echo json_encode(['tables' => count($tables), 'hash' => hash('sha256', json_encode($snapshot))]), PHP_EOL;
