<?php
// Real MariaDB integration checks; all synthetic records are rolled back.
putenv('APP_ENV=testing');putenv('CACHE_STORE=array');putenv('SESSION_DRIVER=array');
foreach (['APP_ENV'=>'testing','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array'] as $key=>$value) $_ENV[$key]=$_SERVER[$key]=$value;
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{User,ClientReview,AuditLog};
use Illuminate\Support\Facades\{DB,Auth,Gate};
use Illuminate\Http\Request;
$checks=[];$failure=null;
function reviewCheck(bool $ok,string $label):void {global $checks;if(!$ok)throw new RuntimeException($label);$checks[]=$label;}
function reviewSnapshot():string {
 $hashes=[];
 foreach(DB::select('SHOW TABLES') as $table){$name=array_values((array)$table)[0];$q=DB::table($name);foreach(DB::select("SELECT COLUMN_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME='PRIMARY' ORDER BY ORDINAL_POSITION",[$name]) as $key)$q->orderBy($key->name);$hashes[$name]=hash('sha256',json_encode($q->get()->all()));}
 return hash('sha256',json_encode($hashes));
}
function reviewRequest(string $method,string $uri,array $data=[],?User $user=null,int $expected=200,bool $json=true,bool $clearLimit=true):array {
 global $app;if($clearLimit)app('cache')->flush();$app['session']->forgetDrivers();$app->forgetInstance('session.store');Auth::forgetGuards();if($user)Auth::guard()->setUser($user->fresh());
 $req=Request::create($uri,$method,$data,[],[],['HTTP_ACCEPT'=>$json?'application/json':'text/html','REMOTE_ADDR'=>'192.0.2.155']);
 $kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);$res=$kernel->handle($req);$kernel->terminate($req,$res);
 reviewCheck($res->getStatusCode()===$expected,$method.' '.$uri.' HTTP '.$expected);
 return ['text'=>$res->getContent(),'json'=>json_decode($res->getContent(),true),'location'=>$res->headers->get('Location')];
}
function reviewPerson(string $role):User {
 $u=new User(['name'=>'Private '.ucfirst($role).' name','email'=>$role.'-'.uniqid().'@example.test','password'=>'FixtureSecure123!']);$u->role=$role;$u->is_active=true;$u->save();return $u;
}
$baseline=reviewSnapshot();DB::beginTransaction();
try {
 reviewCheck(count(DB::select('SHOW TABLES'))===35,'34 existing tables plus one review table');
 $owner=User::where('role','owner')->firstOrFail();$client=reviewPerson('client');$other=reviewPerson('client');$manager=reviewPerson('manager');
 $data=['display_name'=>'Ali K.','rating'=>5,'message'=>'Helpful coaches and a friendly gym atmosphere.','public_consent'=>true];
 $empty=reviewRequest('GET','/feedback',[],null,200,false);reviewCheck(str_contains($empty['text'],'0 approved reviews')&&!str_contains($empty['text'],'NaN'),'Empty public summary');
 reviewRequest('GET','/client/reviews',[],null,302,false);reviewRequest('POST','/client/reviews',$data,null,401);
 reviewRequest('POST','/client/reviews',$data,$owner,403);reviewRequest('POST','/client/reviews',$data,$manager,403);
 foreach([['rating'=>0],['rating'=>6],['rating'=>2.5],['rating'=>'bad'],['message'=>'short'],['message'=>str_repeat('x',2001)],['message'=>[]],['display_name'=>'email@example.test'],['display_name'=>str_repeat('A',41)],['public_consent'=>false]] as $invalid)reviewRequest('POST','/client/reviews',[...$data,...$invalid],$client,422);
 foreach(['user_id'=>$other->id,'status'=>'approved','reviewed_by'=>$owner->id,'reviewed_at'=>'2026-01-01','created_at'=>'2026-01-01','review_hash'=>'fake'] as $key=>$value)reviewRequest('POST','/client/reviews',[...$data,$key=>$value],$client,422);
 reviewRequest('POST','/client/reviews',$data,$client,302);$review=$client->reviews()->firstOrFail();
 reviewCheck($review->status==='pending'&&$review->rating===5&&$review->user_id===$client->id,'Authenticated Client and pending defaults');
 reviewCheck($review->client->id===$client->id&&$review->created_at->isToday(),'Client relationship and server submission timestamp');
 $pending=reviewRequest('GET','/feedback',[],null,200,false);reviewCheck(!str_contains($pending['text'],$data['message']),'Pending review excluded publicly');
 $own=reviewRequest('GET','/client/reviews',[],$client,200,false);reviewCheck(str_contains($own['text'],$data['message'])&&str_contains($own['text'],'Pending'),'Client sees own approval status');
 $otherPage=reviewRequest('GET','/client/reviews',[],$other,200,false);reviewCheck(!str_contains($otherPage['text'],$data['message']),'Client review listing isolation');
 reviewCheck(!Gate::forUser($other)->allows('view',$review),'Review policy prevents another Client viewing');
 reviewRequest('POST','/client/reviews',[...$data,'message'=>'Different feedback describing my recent gym visit.'],$client,422);
 $review->forceFill(['created_at'=>now()->subDays(31)])->save();
 reviewRequest('POST','/client/reviews',[...$data,'message'=>' HELPFUL  COACHES AND A FRIENDLY GYM ATMOSPHERE. '],$client,422);
 try {DB::table('client_reviews')->insert([...$review->getAttributes(),'id'=>999999999]);reviewCheck(false,'Database duplicate constraint');}catch(Illuminate\Database\QueryException $e){reviewCheck(true,'Database duplicate constraint');}
 reviewRequest('GET','/manage/feedback',[],$client,403);reviewRequest('GET','/manage/feedback',[],$manager,403);
 reviewRequest('PUT','/manage/feedback/'.$review->id,['status'=>'approved'],$manager,403);
 reviewRequest('PUT','/manage/feedback/'.$review->id,['status'=>'approved'],$client,403);
 reviewRequest('GET','/manage/feedback',[],$owner,200,false);
 reviewRequest('PUT','/manage/feedback/'.$review->id,['status'=>'deleted'],$owner,422);
 reviewRequest('PUT','/manage/feedback/'.$review->id,['status'=>'approved'],$owner,302);
 $review->refresh();reviewCheck($review->reviewed_by===$owner->id&&$review->reviewed_at!==null,'Owner moderation attribution');
 $published=reviewRequest('GET','/feedback',[],null,200,false);reviewCheck(str_contains($published['text'],$data['message'])&&str_contains($published['text'],'Ali K.'),'Approval automatically publishes review');
 foreach([$client->email,$client->name,(string)$client->password] as $private)reviewCheck(!str_contains($published['text'],$private),'Public review excludes private account information');
 reviewCheck(str_contains($published['text'],'>5.0</strong>')&&str_contains($published['text'],'1 approved review'),'Approved average and total');
 reviewRequest('PUT','/manage/feedback/'.$review->id,['status'=>'hidden'],$owner,302);
 $hidden=reviewRequest('GET','/feedback',[],null,200,false);reviewCheck(!str_contains($hidden['text'],$data['message'])&&ClientReview::find($review->id)!==null,'Hidden review retained and unpublished');
 reviewRequest('PUT','/manage/feedback/'.$review->id,['status'=>'rejected'],$owner,302);
 $rejected=reviewRequest('GET','/feedback',[],null,200,false);reviewCheck(!str_contains($rejected['text'],$data['message']),'Rejected review excluded publicly');
 reviewRequest('PUT','/owner/managers/'.$manager->id.'/permissions',['permissions'=>['feedback.manage']],$owner,200);
 reviewRequest('GET','/manage/feedback',[],$manager,200,false);
 reviewRequest('PUT','/manage/feedback/'.$review->id,['status'=>'approved'],$manager,302);
 reviewCheck($review->fresh()->reviewed_by===$manager->id,'Explicitly approved Manager moderates');
 reviewRequest('PUT','/owner/managers/'.$manager->id.'/permissions',['permissions'=>[]],$owner,200);
 reviewRequest('PUT','/owner/managers/'.$manager->id.'/permissions',['permissions'=>['feedback.manage']],$manager,403);reviewRequest('GET','/manage/feedback',[],$manager,403);reviewRequest('PUT','/manage/feedback/'.$review->id,['status'=>'hidden'],$manager,403);
 reviewCheck(AuditLog::where('entity_id',$review->id)->whereIn('action',['feedback.approved','feedback.rejected','feedback.hidden'])->count()===4,'Moderation history recorded without deleting review');
 reviewRequest('POST','/client/reviews',[...$data,'message'=>'A new review after thirty days about consistent training.'],$client,302);
 reviewCheck($client->reviews()->count()===2,'New feedback allowed after cooldown');
 $client->is_active=false;$client->save();reviewRequest('POST','/client/reviews',$data,$client,403);
 $first=$other->reviews()->create(['display_name'=>'Gym member','rating'=>3,'message'=>'<script>alert("review-xss")</script> Friendly staff and useful equipment.','review_hash'=>ClientReview::messageHash('Other review'),'status'=>'approved']);
 $safe=reviewRequest('GET','/feedback',[],null,200,false);reviewCheck(str_contains($safe['text'],'>4.0</strong>')&&str_contains($safe['text'],'2 approved reviews'),'Average excludes all unapproved reviews');
 reviewCheck(str_contains($safe['text'],'&lt;script&gt;')&&!str_contains($safe['text'],'<script>alert'),'Review content escaped against stored XSS');
 for($i=0;$i<12;$i++)$other->reviews()->create(['display_name'=>'Member '.$i,'rating'=>4,'message'=>'Pagination review number '.$i.' with helpful feedback.','review_hash'=>hash('sha256','page-'.$i),'status'=>'approved']);
 $page=reviewRequest('GET','/feedback',[],null,200,false);reviewCheck(substr_count($page['text'],'class="review-card"')===12&&str_contains($page['text'],'page=2'),'Public reviews paginated');
 $page2=reviewRequest('GET','/feedback?page=2',[],null,200,false);reviewCheck(substr_count($page2['text'],'class="review-card"')===2,'Second public page');
 $limited=reviewPerson('client');app('cache')->flush();
 for($i=0;$i<6;$i++)reviewRequest('POST','/client/reviews',$data,$limited,$i===0?302:($i===5?429:422),true,false);
 reviewCheck($limited->reviews()->count()===1,'Spam attempts create only one review');
 foreach([['rating'=>0],['rating'=>6],['status'=>'deleted']] as $invalid) {
  try {DB::table('client_reviews')->where('id',$first->id)->update($invalid);reviewCheck(false,'Database review constraints');}
  catch(Illuminate\Database\QueryException $e){reviewCheck(true,'Database review constraints '.json_encode($invalid));}
 }
 foreach([$other,$manager] as $historicalUser) {
  try {DB::table('users')->where('id',$historicalUser->id)->delete();reviewCheck(false,'Review historical account references');}
  catch(Illuminate\Database\QueryException $e){reviewCheck(true,'Review historical account references retained');}
 }
 $allPermissions=array_keys(config('gym.manager_permissions'));
 reviewRequest('PUT','/manage/accounts/managers/'.$manager->id,['name'=>$manager->name,'email'=>$manager->email,'is_active'=>true,'permissions'=>$allPermissions],$owner,302);
 reviewCheck($manager->managerPermissions()->count()===count($allPermissions),'Dashboard accepts every permission including feedback');
 reviewRequest('PUT','/owner/managers/'.$manager->id.'/permissions',['permissions'=>$allPermissions],$owner,200);
 reviewRequest('DELETE','/manage/feedback/'.$review->id,[],$owner,405);
 foreach(['/owner/dashboard','/manager/dashboard','/client/dashboard'] as $uri)reviewRequest('GET',$uri,[],match($uri){'/owner/dashboard'=>$owner,'/manager/dashboard'=>$manager,default=>$other},200,false);
} catch(Throwable $error){$failure=$error instanceof RuntimeException?$error->getMessage():get_class($error).' at '.basename($error->getFile()).':'.$error->getLine();}
finally {while(DB::transactionLevel()>0)DB::rollBack();}
$preserved=$baseline===reviewSnapshot();$report=['passed'=>$failure===null&&$preserved,'checks'=>count($checks),'failure'=>$failure,'databasePreserved'=>$preserved,'checksExecuted'=>$checks];
$folder=storage_path('app/private/feedback-verification');if(!is_dir($folder))mkdir($folder,0700,true);file_put_contents($folder.'/backend-tests.json',json_encode($report,JSON_PRETTY_PRINT));echo json_encode(array_diff_key($report,['checksExecuted'=>true]),JSON_PRETTY_PRINT),PHP_EOL;exit($report['passed']?0:1);

