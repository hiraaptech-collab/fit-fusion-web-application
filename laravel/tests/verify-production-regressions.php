<?php
// No production connection: SQLite in memory, HTTP fakes, no real API credentials.
foreach (['APP_ENV'=>'testing','DB_CONNECTION'=>'sqlite','DB_DATABASE'=>':memory:',
    'CACHE_STORE'=>'array','SESSION_DRIVER'=>'array','LOG_CHANNEL'=>'null'] as $key=>$value) {
    putenv($key.'='.$value); $_ENV[$key]=$_SERVER[$key]=$value;
}
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\{DB,Schema,Http};
use Illuminate\Database\Schema\Blueprint;
use App\Services\FusionAiService;
use App\Services\AiUsageService;

$count = 0;
function regression(bool $condition,string $label): void {
    global $count;
    if (!$condition) throw new RuntimeException($label);
    $count++;
}
regression(DB::connection()->getDatabaseName()===':memory:','In-memory test isolation');
Schema::create('gym_settings',function(Blueprint $table) {
    $table->unsignedTinyInteger('id')->primary();
    $table->string('name')->default('FitFusion');
    $table->timestamps();
});
$migration = require __DIR__.'/../database/migrations/2026_10_15_000001_initialize_gym_settings.php';
$migration->up();
regression(DB::table('gym_settings')->where('id',1)->exists(),'Migration initializes required row');
DB::table('gym_settings')->where('id',1)->update(['name'=>'Existing Gym']);
$migration->up(); $migration->down();
regression(DB::table('gym_settings')->count()===1 && DB::table('gym_settings')->value('name')==='Existing Gym','Rerun and rollback preserve settings');
config(['services.fusion_ai.require_provider'=>true,'services.fusion_ai.api_key'=>'']);
$messages = [['role'=>'user','content'=>'Hello']];
$ai = app(FusionAiService::class);
$result = $ai->reply($messages);
regression($result['status']===503 && !isset($result['body']['reply']),'Missing key cannot produce canned success');
app('request')->attributes->set('fusion_ai_provider_allowed',false);
$result = $ai->reply($messages);
regression($result['status']===403 && $result['body']['code']==='AI_LOGIN_REQUIRED','Guest access denial is explicit');
app('request')->attributes->set('fusion_ai_provider_allowed',true);
config(['services.fusion_ai.api_key'=>'dummy-regression-key']);
$app->instance(AiUsageService::class,new class extends AiUsageService {
    public function reserve(array $messages): array { return []; }
    public function complete(array $reservation,?array $result,array $usage): void {}
});
foreach ([401,403,404,500,503] as $status) {
    Http::swap(new Illuminate\Http\Client\Factory());
    Http::preventStrayRequests();
    Http::fake(fn()=>Http::response(['error'=>'private upstream error'],$status));
    $result=$ai->reply($messages);
    regression($result['status']===503 && !isset($result['body']['reply']),'Provider failure must not become canned success');
}
Http::swap(new Illuminate\Http\Client\Factory());
Http::preventStrayRequests();
Http::fake(fn()=>Http::response(['choices'=>[['message'=>['content'=>'Provider fixture']]]]));
$result=$ai->reply($messages);
regression($result['status']===200 && $result['body']['reply']==='Provider fixture' && !isset($result['body']['source']),'Real provider response path remains intact');
echo json_encode(['passed'=>$count,'database'=>'memory only','realProviderCalled'=>false]).PHP_EOL;
