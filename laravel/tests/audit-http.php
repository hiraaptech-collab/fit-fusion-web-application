<?php
// Local audit server only. Real credentials are never loaded into this test server.
if(($_SERVER['SERVER_PORT']??'')!=8006||($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1'){http_response_code(403);exit;}
$app=require __DIR__.'/audit-bootstrap.php';
use Illuminate\Support\Facades\{DB,Http};
$queries=0;$queryMs=0;
DB::listen(function($event)use(&$queries,&$queryMs){$queries++;$queryMs+=$event->time;});
Http::preventStrayRequests();
Http::fake(['api.groq.com/*'=>function(){usleep(150000);return Http::response(['choices'=>[['message'=>['content'=>'A simulated provider response for isolated performance testing.']]],'usage'=>['prompt_tokens'=>500,'completion_tokens'=>20,'total_tokens'=>520]],200);}]);
$request=Illuminate\Http\Request::capture();
$actor=$request->header('X-Audit-Client','0');
if(!ctype_digit($actor)||(int)$actor>200){http_response_code(400);exit;}
$request->server->set('REMOTE_ADDR','192.0.2.'.max(1,(int)$actor));
$kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);
$response=$kernel->handle($request);
$response->headers->set('X-Audit-Query-Count',(string)$queries);
$response->headers->set('X-Audit-Query-Ms',(string)round($queryMs,2));
$response->headers->set('X-Audit-PHP-Peak-MB',(string)round(memory_get_peak_usage(true)/1048576,2));
$response->send();$kernel->terminate($request,$response);
