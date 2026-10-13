<?php
// One real provider call plus fallback/timeout checks; all history fixtures roll back.
putenv('APP_ENV=testing');putenv('CACHE_STORE=array');putenv('SESSION_DRIVER=array');
foreach(['APP_ENV'=>'testing','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array'] as $key=>$value)$_ENV[$key]=$_SERVER[$key]=$value;
require __DIR__.'/../vendor/autoload.php';$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\User;use Illuminate\Http\Request;use Illuminate\Support\Facades\{DB,Auth,Http};use Illuminate\Http\Client\{Factory,ConnectionException};
function chatRequest(string $message):array {
    global $app,$client;Auth::forgetGuards();Auth::guard()->setUser($client);
    $request=Request::create('/client/ai','POST',['message'=>$message,'consent'=>true],[],[],['HTTP_ACCEPT'=>'application/json','REMOTE_ADDR'=>'192.0.2.155']);
    $kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);$response=$kernel->handle($request);$kernel->terminate($request,$response);
    $body=json_decode($response->getContent(),true);return ['status'=>$response->getStatusCode(),'source'=>$body['source']??'provider','replyLength'=>strlen($body['reply']??''),'code'=>$body['code']??null,'conversation_id'=>$body['conversation_id']??null];
}
$report=[];DB::beginTransaction();
try {
    $client=new User(['name'=>'Live AI Fixture','email'=>'live-ai-'.uniqid().'@example.test','password'=>'FixtureSecure123!']);$client->role='client';$client->is_active=true;$client->save();
    $report['realProvider']=chatRequest('Give one short, general strength-training tip for a healthy beginner.');
    $report['realProvider']['passed']=$report['realProvider']['status']===200&&$report['realProvider']['source']==='provider'&&$report['realProvider']['replyLength']>0;
    $report['realProvider']['messagesSaved']=$client->aiConversations()->first()?->messages()->count()??0;
    config(['services.fusion_ai.api_key'=>'']);$report['fallback']=chatRequest('Hello');$report['fallback']['passed']=$report['fallback']['status']===200&&$report['fallback']['source']==='local';
    config(['services.fusion_ai.api_key'=>'dummy-fixture-key']);Http::swap(new Factory());Http::fake(fn()=>throw new ConnectionException('cURL error 28: Operation timed out'));
    $before=$client->aiConversations()->count();$report['timeout']=chatRequest('Timeout fixture');$report['timeout']['passed']=$report['timeout']['status']===504&&$report['timeout']['code']==='TIMEOUT'&&$client->aiConversations()->count()===$before;
    foreach($report as &$result)unset($result['conversation_id']);unset($result);
} catch(Throwable $error){$report['failure']=get_class($error);}
finally {DB::rollBack();}
$report['fixturesRolledBack']=true;$report['passed']=($report['realProvider']['passed']??false)&&($report['fallback']['passed']??false)&&($report['timeout']['passed']??false);
$folder=storage_path('app/private/phase4-5-verification');if(!is_dir($folder))mkdir($folder,0700,true);file_put_contents($folder.'/live-ai-tests.json',json_encode($report,JSON_PRETTY_PRINT));echo json_encode($report,JSON_PRETTY_PRINT),PHP_EOL;exit($report['passed']?0:1);
