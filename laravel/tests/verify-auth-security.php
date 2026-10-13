<?php
// Local verification prints only results, never credentials or stored account details.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$secrets = array_filter([config('database.connections.mariadb.password'), config('services.fusion_ai.api_key')], fn ($value) => is_string($value) && strlen($value) >= 12);
$exposed = [];
$scanned = 0;
foreach ([public_path(), resource_path('views'), storage_path('logs'), storage_path('app/private/phase4-4-verification'), storage_path('app/private/phase4-5-verification'), storage_path('app/private/feedback-verification')] as $folder) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if (! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['php','js','cjs','css','json','html','log','md'])) { continue; }
        if (str_contains($file->getPathname(), 'chrome-profile-')) { continue; }
        $scanned++;
        $content = file_get_contents($file->getPathname());
        foreach ($secrets as $secret) {
            if (str_contains($content, $secret)) { $exposed[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()); break; }
        }
    }
}
$report = [
    'passed' => $exposed === [] && !config('app.debug') && config('session.encrypt') && config('session.http_only'),
    'filesScanned' => $scanned, 'credentialMatches' => $exposed,
    'debugDisabled' => !config('app.debug'), 'sessionEncrypted' => config('session.encrypt'),
    'httpOnly' => config('session.http_only'), 'sameSite' => config('session.same_site'),
    'tables' => count(DB::select('SHOW TABLES')), 'owners' => App\Models\User::withTrashed()->where('role','owner')->count(),
    'localResetPreviews' => config('gym.password_reset_delivery') === 'preview',
    'provider' => parse_url(config('services.fusion_ai.base_url'), PHP_URL_HOST), 'model' => config('services.fusion_ai.model'),
    'windowsHiddenInputHelperAvailable' => file_exists(base_path('vendor/symfony/console/Resources/bin/hiddeninput.exe')),
];
file_put_contents(storage_path('app/private/phase4-4-verification/security-checks.json'), json_encode($report, JSON_PRETTY_PRINT));
echo json_encode($report, JSON_PRETTY_PRINT), PHP_EOL;
exit($report['passed'] ? 0 : 1);

