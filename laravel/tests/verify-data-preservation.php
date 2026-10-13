<?php
// Compare actual stored rows before and after verification, without printing their contents.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if (DB::connection()->getDatabaseName() !== 'fitfusion_db') {
    throw new RuntimeException('Unexpected database.');
}
$tables = DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME');
$snapshot = [];
foreach ($tables as $table) {
    $keys = DB::select("SELECT COLUMN_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = 'PRIMARY' ORDER BY ORDINAL_POSITION", [$table->name]);
    $query = DB::table($table->name);
    foreach ($keys as $key) { $query->orderBy($key->name); }
    $rows = $query->get()->all();
    $snapshot[$table->name] = ['rows' => count($rows), 'hash' => hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR))];
}
$folder = storage_path('app/private/phase4-4-verification');
$file = $folder.'/database-row-baseline.json';
if (($argv[1] ?? '') === 'baseline') {
    if (file_exists($file)) { throw new RuntimeException('Baseline already exists; it will not be overwritten.'); }
    file_put_contents($file, json_encode($snapshot, JSON_PRETTY_PRINT));
    echo json_encode(['baselineSaved' => true, 'tables' => count($snapshot)]), PHP_EOL;
} else {
    $baseline = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $changed = [];
    $added = [];
    foreach ($snapshot as $name => $details) {
        if (($baseline[$name] ?? null) === $details) { continue; }
        // New records in a previously empty table cannot replace any baseline rows.
        if (isset($baseline[$name]) && $baseline[$name]['rows'] === 0 && $details['rows'] > 0) {
            $added[$name] = $details['rows'];
        } else { $changed[] = $name; }
    }
    $report = ['passed' => $changed === [] && count($baseline) === count($snapshot), 'tables' => count($snapshot), 'changedExistingTables' => $changed, 'newRowsInPreviouslyEmptyTables' => $added];
    file_put_contents($folder.'/database-preservation.json', json_encode($report, JSON_PRETTY_PRINT));
    echo json_encode($report), PHP_EOL;
    exit($report['passed'] ? 0 : 1);
}