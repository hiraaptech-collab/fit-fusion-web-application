<?php
// Local-only browser fixture: every request rolls back all seeded/changed rows.
require __DIR__.'/../vendor/autoload.php';
use App\Models\{User,Trainer,Worker,MembershipPlan,Membership,Payment,Refund,Expense,StaffSchedule,StaffAttendance,ClientPlanAssignment,WorkoutPlan,DietPlan,FitnessProgress,AiConversation,GymSetting};
use Illuminate\Support\Facades\{DB,Auth};use Illuminate\Contracts\Console\Kernel;use Illuminate\Cookie\CookieValuePrefix;
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
if(!app()->environment('local')||DB::connection()->getDatabaseName()!=='fitfusion_db')throw new RuntimeException('Local verification only.');
$folder=storage_path('app/private/feedback-verification');if(!is_dir($folder))mkdir($folder,0700,true);
$configFile=$folder.'/.browser-fixture.json';$action=$argv[1]??'';
if(PHP_SAPI!=='cli-server'&&$action==='prepare'){
    $ids=[];foreach(['users','trainers','workers','membership_plans','memberships','payments','refunds','expenses','staff_schedules','staff_attendances','client_plan_assignments','fitness_progress','ai_conversations','messages','inquiries','client_reviews'] as $table)$ids[$table]=(int)DB::table($table)->max('id')+1;
    file_put_contents($configFile,json_encode(['ids'=>$ids,'password'=>password_hash('Aa1!'.bin2hex(random_bytes(12)),PASSWORD_BCRYPT,['cost'=>12])]));echo json_encode(['prepared'=>true]);exit;
}
function dataSnapshot():array {
    $rows=[];$tables=DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME');
    foreach($tables as $table){$q=DB::table($table->name);foreach(DB::select("SELECT COLUMN_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME='PRIMARY' ORDER BY ORDINAL_POSITION",[$table->name]) as $key)$q->orderBy($key->name);$rows[$table->name]=hash('sha256',json_encode($q->get()->all()));}
    return ['tables'=>count($tables),'hash'=>hash('sha256',json_encode($rows))];
}
if(PHP_SAPI!=='cli-server'&&$action==='snapshot'){echo json_encode(dataSnapshot());exit;}
if(!is_file($configFile))throw new RuntimeException('Prepare the browser fixture first.');
$fixture=json_decode(file_get_contents($configFile),true);$ids=$fixture['ids'];
$sessionFolder=storage_path('framework/sessions/review-verification');if(!is_dir($sessionFolder))mkdir($sessionFolder,0700,true);config(['session.files'=>$sessionFolder]);
if(PHP_SAPI==='cli-server'){
    if(($_SERVER['SERVER_NAME']??'')!=='127.0.0.1'||($_SERVER['SERVER_PORT']??'')!=8004){http_response_code(403);exit;}
    $public=realpath(public_path());$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);$file=realpath($public.rawurldecode($path));
    if($file&&str_starts_with($file,$public.DIRECTORY_SEPARATOR)&&is_file($file)&&pathinfo($file,PATHINFO_EXTENSION)!=='php')return false;
}
function fixtureRecord(string $model,array $data,int $id) {
    $record=new $model($data);
    if(DB::table($record->getTable())->where('id',$id)->exists())throw new RuntimeException('Fixture ID collision.');
    $record->id=$id;$record->save();return $record;
}DB::beginTransaction();
try{
    $owner=User::where('role','owner')->where('is_active',true)->firstOrFail();$people=[];
    foreach(['client','client','manager','manager'] as $offset=>$role){
        if(User::withTrashed()->find($ids['users']+$offset))throw new RuntimeException('Fixture ID collision; no existing account will be overwritten.');
        $person=new User(['name'=>$offset===0?'Browser Client':($offset===1?'Other Browser Client':'Browser Manager'),'email'=>'phase45-'.$offset.'@example.test','password'=>$fixture['password']]);$person->id=$ids['users']+$offset;$person->role=$role;$person->is_active=true;$person->save();$people[]=$person;
        if($role==='client')$person->clientProfile()->create([]);else foreach(array_keys(config('gym.manager_permissions')) as $permission)$person->managerPermissions()->create(['permission'=>$permission,'granted_by'=>$owner->id]);
    }
    [$client,$other,$manager,$restricted]=$people;
    $restricted->managerPermissions()->where('permission','feedback.manage')->delete();
    $trainer=fixtureRecord(Trainer::class,['name'=>'Browser Trainer','is_active'=>true,'created_by'=>$owner->id],$ids['trainers']);
    $worker=fixtureRecord(Worker::class,['name'=>'Browser Worker','job_title'=>'Reception','is_active'=>true,'created_by'=>$owner->id],$ids['workers']);
    $plan=fixtureRecord(MembershipPlan::class,['name'=>'Browser Monthly','slug'=>'browser-monthly','duration_days'=>30,'price'=>'100.00','currency'=>'PKR','is_active'=>true,'created_by'=>$owner->id],$ids['membership_plans']);
    $membership=fixtureRecord(Membership::class,['user_id'=>$client->id,'membership_plan_id'=>$plan->id,'plan_name'=>$plan->name,'duration_days'=>30,'agreed_price'=>'100.00','currency'=>'PKR','starts_on'=>\App\Support\GymClock::today(),'ends_on'=>\App\Support\GymClock::today()->addDays(29),'status'=>'active','created_by'=>$owner->id],$ids['memberships']);
    $payment=fixtureRecord(Payment::class,['user_id'=>$client->id,'membership_id'=>$membership->id,'receipt_number'=>'FF-BROWSER-FIXTURE','amount'=>'65.00','currency'=>'PKR','method'=>'cash','status'=>'paid','paid_at'=>now(),'recorded_by'=>$owner->id],$ids['payments']);
    fixtureRecord(Refund::class,['payment_id'=>$payment->id,'amount'=>'10.00','currency'=>'PKR','reference'=>'RF-BROWSER-FIXTURE','reason'=>'Fixture correction','status'=>'completed','refunded_at'=>now(),'recorded_by'=>$owner->id],$ids['refunds']);
    fixtureRecord(Expense::class,['description'=>'Browser gym expense','category'=>'Operations','amount'=>'200.00','currency'=>'PKR','incurred_on'=>\App\Support\GymClock::today(),'recorded_by'=>$owner->id],$ids['expenses']);
    $schedule=fixtureRecord(StaffSchedule::class,['trainer_id'=>$trainer->id,'starts_at'=>\App\Support\GymClock::today()->setTime(8,0),'ends_at'=>\App\Support\GymClock::today()->setTime(10,0),'created_by'=>$owner->id],$ids['staff_schedules']);
    fixtureRecord(StaffAttendance::class,['trainer_id'=>$trainer->id,'staff_schedule_id'=>$schedule->id,'attendance_date'=>\App\Support\GymClock::today(),'status'=>'present','recorded_by'=>$owner->id],$ids['staff_attendances']);
    fixtureRecord(ClientPlanAssignment::class,['user_id'=>$client->id,'workout_plan_id'=>WorkoutPlan::first()->id,'starts_on'=>\App\Support\GymClock::today(),'assigned_by'=>$owner->id,'notes'=>'Browser private workout'], $ids['client_plan_assignments']);
    fixtureRecord(ClientPlanAssignment::class,['user_id'=>$client->id,'diet_plan_id'=>DietPlan::first()->id,'starts_on'=>\App\Support\GymClock::today(),'assigned_by'=>$owner->id], $ids['client_plan_assignments']+1);
    fixtureRecord(FitnessProgress::class,['user_id'=>$client->id,'recorded_on'=>\App\Support\GymClock::today(),'weight_kg'=>70,'height_cm'=>175,'bmi'=>22.86,'notes'=>'Browser private progress'],$ids['fitness_progress']);
    $conversation=fixtureRecord(AiConversation::class,['user_id'=>$client->id,'title'=>'Browser saved fitness conversation','consented_at'=>now()],$ids['ai_conversations']);
    $conversation->messages()->create(['role'=>'user','content'=>'Hello']);$conversation->messages()->create(['role'=>'assistant','content'=>app(App\Services\FusionAiService::class)->localResponse('Hello'),'source'=>'local']);
    fixtureRecord(App\Models\Message::class,['sender_id'=>$client->id,'recipient_id'=>$owner->id,'subject'=>'Browser membership question','body'=>'How do I renew my membership?'],$ids['messages']);
    fixtureRecord(App\Models\Inquiry::class,['user_id'=>$client->id,'kind'=>'contact','name'=>$client->name,'email'=>$client->email,'subject'=>'Browser inquiry','message'=>'Please explain renewal details.'],$ids['inquiries']);
    $client->notifications()->create(['id'=>'11111111-2222-4333-8444-555555555555','type'=>'gym.notice','data'=>['title'=>'Browser gym notice','body'=>'Your membership is active.']]);
    for ($i=0; $i<16; $i++) {
        $status=match($i){1=>'pending',2=>'rejected',3=>'hidden',default=>'approved'};
        if (App\Models\ClientReview::find($ids['client_reviews']+$i)) throw new RuntimeException('Review fixture ID collision.');
        $review=new App\Models\ClientReview([
            'display_name'=>$i===0?'Client Alias':'Gym Member '.$i,
            'rating'=>$i===0?5:4,
            'message'=>$status==='approved'?'A friendly gym with helpful coaches. Review '.$i.'.':'PRIVATE '.strtoupper($status).' REVIEW '.$i,
            'review_hash'=>hash('sha256','browser-review-'.$i), 'status'=>$status,
        ]);
        $review->id=$ids['client_reviews']+$i;
        $review->user_id=$i===0?$client->id:$other->id;
        $review->created_at=now()->subDays(45);
        $review->save();
    }
    if(PHP_SAPI==='cli-server'){
        $kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);$request=Illuminate\Http\Request::capture();$response=$kernel->handle($request);$response->send();$kernel->terminate($request,$response);
    } elseif(str_starts_with($action,'session-')) {
        $user=match(substr($action,8)){'owner'=>$owner,'manager'=>$manager,'client'=>$client,'other'=>$other,'restricted'=>$restricted,default=>throw new RuntimeException('Unknown fixture role.')};
        $session=app('session')->driver();$session->start();Auth::login($user);$session->save();$name=config('session.cookie');$prefix=CookieValuePrefix::create($name,app('encrypter')->getKey());
        echo json_encode(['name'=>$name,'value'=>app('encrypter')->encrypt($prefix.$session->getId(),false)]);
    } else {throw new RuntimeException('Unknown fixture action.');}
} finally {DB::rollBack();}



