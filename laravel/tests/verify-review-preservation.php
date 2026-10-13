<?php
// Read and compare hashes; never print credentials or private database content.
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$backup=$argv[1]??'';
if(!is_file($backup.'/database-snapshot.json')) {echo 'Provide the feedback backup folder.',PHP_EOL;exit(1);}
$baseline=json_decode(file_get_contents($backup.'/database-snapshot.json'),true);$dataChanges=[];
foreach($baseline as $table=>$original) {
 if($table==='migrations')continue;
 $query=DB::table($table);
 foreach(DB::select("SELECT COLUMN_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME='PRIMARY' ORDER BY ORDINAL_POSITION",[$table]) as $key)$query->orderBy($key->name);
 if(hash('sha256',json_encode($query->get()->all()))!==$original['hash'])$dataChanges[]=$table;
}
$normalAccountActivity=[];$unexpectedDataChanges=$dataChanges;
if(array_intersect($dataChanges,['users','audit_logs'])) {
 $dump=file_get_contents($backup.'/fitfusion_db.sql');
 foreach(['users','audit_logs'] as $table) {
  if(!in_array($table,$dataChanges,true))continue;
  preg_match_all('/INSERT INTO `'.$table.'` \((.*?)\) VALUES \((.*?)\);/s',$dump,$matches,PREG_SET_ORDER);
  $safe=true;$ids=[];
  foreach($matches as $match) {
   $columns=array_map(fn($column)=>trim($column,' `'),explode(',',$match[1]));
   $old=array_combine($columns,str_getcsv($match[2],',',"'",'\\'));
   $ids[]=$old['id'];$current=DB::table($table)->where('id',$old['id'])->first();
   if(!$current){$safe=false;continue;}
   foreach((array)$current as $column=>$value) {
    $previous=$old[$column]??null;if($previous==='NULL')$previous=null;
    if((string)$value===(string)$previous)continue;
    if($table==='users'&&in_array($column,['updated_at','last_login_at'],true)&&$old['role']==='owner') {
     if(!DB::table('audit_logs')->where('user_id',$current->id)->where('action','auth.login')->where('created_at','>=',$current->last_login_at)->exists())$safe=false;
    } else {$safe=false;}
   }
  }
  $newRows=DB::table($table)->whereNotIn('id',$ids);
  if($table==='users'&&$newRows->exists())$safe=false;
  if($table==='audit_logs'&&(clone $newRows)->where('action','!=','auth.login')->exists())$safe=false;
  if($safe){$unexpectedDataChanges=array_values(array_diff($unexpectedDataChanges,[$table]));$normalAccountActivity[]=$table;}
 }
}
$root=dirname(base_path());$old=dirname($root).'/fit-fusion-phase4-4-backup-20261010-025920/phase4-4-file-hashes.json';
$originals=json_decode(ltrim(file_get_contents($old),chr(239).chr(187).chr(191)),true,512,JSON_THROW_ON_ERROR);$originalCount=0;$fileChanges=[];
foreach($originals as $entry){if(preg_match('~^laravel[/\\\\]~i',$entry['Path']))continue;$originalCount++;$file=$root.'/'.$entry['Path'];if(!is_file($file)||strtoupper(hash_file('sha256',$file))!==$entry['Hash'])$fileChanges[]=$entry['Path'];}
$envBefore=Dotenv\Dotenv::parse(file_get_contents($backup.'/laravel/.env'));$envNow=Dotenv\Dotenv::parse(file_get_contents(base_path('.env')));$configurationSame=true;
foreach(['APP_KEY','DB_DATABASE','DB_USERNAME','DB_PASSWORD','AI_API_KEY','AI_BASE_URL','AI_MODEL'] as $key)if(($envBefore[$key]??null)!==($envNow[$key]??null))$configurationSame=false;
$protectedChanges=[];$protectedCount=0;
foreach(json_decode(ltrim(file_get_contents($backup.'/file-hashes.json'),chr(239).chr(187).chr(191)),true,512,JSON_THROW_ON_ERROR) as $entry){$path=str_replace('\\','/',$entry['Path']);if(preg_match('~^public/.*\.(jpg|jpeg|png|gif|webp|svg|avif)$~i',$path)||in_array($path,['public/assets/style.css','app/Services/FusionAiService.php','config/services.php','resources/prompts/fusion-ai.txt','resources/data/fusion-ai-fallbacks.json'])){$protectedCount++;if(strtoupper(hash_file('sha256',base_path($entry['Path'])))!==$entry['Hash'])$protectedChanges[]=$entry['Path'];}}
$report=['passed'=>$unexpectedDataChanges===[]&&$fileChanges===[]&&$protectedChanges===[]&&$configurationSame,'originalFilesChecked'=>$originalCount,'originalFilesChanged'=>$fileChanges,'existingDataTablesChecked'=>count($baseline)-1,'existingDataTablesChanged'=>$dataChanges,'normalOwnerLoginActivity'=>$normalAccountActivity,'unexpectedDataChanges'=>$unexpectedDataChanges,'protectedAssetsChecked'=>$protectedCount,'protectedAssetsChanged'=>$protectedChanges,'credentialConfigurationUnchanged'=>$configurationSame,'tables'=>count(DB::select('SHOW TABLES')),'owners'=>App\Models\User::where('role','owner')->count(),'storedReviews'=>App\Models\ClientReview::count()];
$folder=storage_path('app/private/feedback-verification');if(!is_dir($folder))mkdir($folder,0700,true);file_put_contents($folder.'/preservation-tests.json',json_encode($report,JSON_PRETTY_PRINT));echo json_encode($report,JSON_PRETTY_PRINT),PHP_EOL;exit($report['passed']?0:1);


