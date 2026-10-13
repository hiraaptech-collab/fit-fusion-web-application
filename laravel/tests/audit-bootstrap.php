<?php
// This bootstrap is used only by private, local audit scripts and the test server.
require_once __DIR__.'/../vendor/autoload.php';
$folder=__DIR__.'/../storage/app/private/production-audit';
$mode=getenv('FITFUSION_AUDIT_MODE')==='functional'?'functional':'load';
$credentials=json_decode(file_get_contents($folder.($mode==='functional'?'/.functional-database.json':'/.database.json')),true,512,JSON_THROW_ON_ERROR);
if(!preg_match('/^fitfusion_audit_[a-z0-9_]+$/',$credentials['database']))throw new RuntimeException('Unsafe test database name.');
foreach([
 'APP_ENV'=>PHP_SAPI==='cli'?'testing':'local','APP_DEBUG'=>'false','APP_URL'=>'http://127.0.0.1:8006',
 'APP_KEY'=>$credentials['app_key'],'DB_CONNECTION'=>'mariadb','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3306',
 'DB_DATABASE'=>$credentials['database'],'DB_USERNAME'=>$credentials['username'],'DB_PASSWORD'=>$credentials['password'],
 'LARAVEL_STORAGE_PATH'=>$folder.($mode==='functional'?'/runtime-functional':'/runtime'),'APP_CONFIG_CACHE'=>$folder.'/runtime/framework/audit-config.php',
 'SESSION_DRIVER'=>'file','SESSION_COOKIE'=>'fitfusion_audit_session','CACHE_STORE'=>'file','MAIL_MAILER'=>'array',
 'AI_API_KEY'=>'fitfusion-dummy-audit-key','AI_BASE_URL'=>'https://api.groq.com/openai',
] as $key=>$value){putenv($key.'='.$value);$_ENV[$key]=$_SERVER[$key]=$value;}
$app=require __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($folder.($mode==='functional'?'/runtime-functional':'/runtime'));
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if(Illuminate\Support\Facades\DB::connection()->getDatabaseName()!==$credentials['database'])throw new RuntimeException('Test isolation failed.');
return $app;


