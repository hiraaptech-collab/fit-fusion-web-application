<?php
// Local integration checks use the existing MariaDB and roll every fixture back.
putenv('APP_ENV=testing');putenv('CACHE_STORE=array');putenv('SESSION_DRIVER=array');
foreach(['APP_ENV'=>'testing','CACHE_STORE'=>'array','SESSION_DRIVER'=>'array'] as $key=>$value) $_ENV[$key]=$_SERVER[$key]=$value;
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{User,Trainer,Worker,StaffSchedule,StaffAttendance,MembershipPlan,Membership,Payment,Refund,Expense,WorkoutPlan,DietPlan,Exercise,ClientPlanAssignment,FitnessProgress,Inquiry,Message,AiConversation,AuditLog};
use Illuminate\Support\Facades\{DB,Auth,Http,Storage,Hash};
use Illuminate\Http\Request;
$checks=[];$failure=null;$folder=storage_path('app/private/phase4-5-verification');
if(!is_dir($folder)) mkdir($folder,0700,true);
config(['filesystems.disks.local.root'=>$folder.'/test-mail','mail.default'=>'array']);Storage::forgetDisk('local');
function check(bool $condition,string $name):void {global $checks;if(!$condition)throw new RuntimeException('Check failed: '.$name);$checks[]=$name;}
function snapshot():string {
    $result=[];
    foreach(DB::select('SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME') as $table){
        $query=DB::table($table->name);foreach(DB::select("SELECT COLUMN_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME='PRIMARY' ORDER BY ORDINAL_POSITION",[$table->name]) as $key)$query->orderBy($key->name);
        $result[$table->name]=hash('sha256',json_encode($query->get()->all()));
    }return hash('sha256',json_encode($result));
}
function managementRequest(string $method,string $uri,array $data=[],?User $user=null,bool $html=false):array {
    global $app;$app['session']->forgetDrivers();$app->forgetInstance('session.store');Auth::forgetGuards();if($user)Auth::guard()->setUser($user->fresh());
    $request=Request::create($uri,$method,$data,[],[],['HTTP_ACCEPT'=>$html?'text/html':'application/json','REMOTE_ADDR'=>'192.0.2.144']);
    $kernel=$app->make(Illuminate\Contracts\Http\Kernel::class);$response=$kernel->handle($request);$kernel->terminate($request,$response);
    return ['status'=>$response->getStatusCode(),'json'=>json_decode($response->getContent(),true),'text'=>$response->getContent(),'location'=>$response->headers->get('Location')];
}
function status(string $method,string $uri,array $data,?User $user,int $expected,string $name,bool $html=false):array {
    $result=managementRequest($method,$uri,$data,$user,$html);check($result['status']===$expected,$name.' (HTTP '.$result['status'].')');return $result;
}
function client(string $name):User {
    $user=new User(['name'=>$name,'email'=>strtolower(str_replace(' ','-',$name)).'-'.uniqid().'@example.test','password'=>'FixtureSecure123!']);$user->role='client';$user->is_active=true;$user->save();$user->clientProfile()->create([]);return $user;
}
$baseline=snapshot();DB::beginTransaction();
try {
        App\Models\GymSetting::findOrFail(1)->update(['timezone'=>'Asia/Karachi']);
    Carbon\Carbon::setTestNow(Carbon\Carbon::parse('2026-10-10 20:30:00','UTC'));
    check(App\Support\GymClock::today()->toDateString()==='2026-10-11','Membership calendar uses gym timezone across midnight');
    Carbon\Carbon::setTestNow();
    check(App\Support\GymClock::utcInput('2026-10-11T08:00')==='2026-10-11 03:00:00','Gym schedule input stored in UTC');
    check(App\Support\GymClock::display(Carbon\Carbon::parse('2026-10-11 03:00:00','UTC'),'H:i')==='08:00','UTC schedule displayed in gym local time');
    $owner=User::where('role','owner')->where('is_active',true)->firstOrFail();$client=client('Alpha Client');$other=client('Beta Client');
    $managerData=['name'=>'Fixture Manager','email'=>'manager-'.uniqid().'@example.test','password'=>'FixtureSecure123!','password_confirmation'=>'FixtureSecure123!','is_active'=>true,'permissions'=>[]];
    status('POST','/manage/accounts/managers',$managerData,$client,403,'Client cannot create Manager');
    status('POST','/manage/accounts/managers',$managerData,$owner,302,'Owner creates Manager');$manager=User::where('email',$managerData['email'])->firstOrFail();
    check($manager->role==='manager'&&Hash::check('FixtureSecure123!',$manager->password),'Manager uses existing secure hashing');
    status('PUT','/manage/accounts/managers/'.$owner->id,[...$managerData,'email'=>$owner->email],$owner,404,'Manager editor cannot target Owner');
    status('PUT','/manage/accounts/managers/'.$manager->id,$managerData,$manager,403,'Manager cannot edit own permissions');
    foreach(['/owner/dashboard','/gym-settings','/audit-logs','/manage/accounts/managers'] as $uri)status('GET',$uri,[],$manager,403,'Owner-only route '.$uri);
    foreach(['/manage/accounts/clients','/manage/staff/trainers','/manage/staff/workers','/manage/staff-operations/schedules','/manage/staff-operations/attendance','/manage/memberships','/manage/membership-plans','/manage/finance','/manage/fitness-plans/workouts','/manage/fitness-plans/diets','/messages','/manage/reports'] as $uri)status('GET',$uri,[],$manager,403,'Unapproved Manager route '.$uri);
    status('GET','/manager/dashboard',[],$manager,200,'Manager empty dashboard',true);
    foreach(array_keys(config('gym.manager_permissions')) as $permission)$manager->managerPermissions()->create(['permission'=>$permission,'granted_by'=>$owner->id]);
    foreach(['/owner/dashboard','/manage/accounts/managers','/manage/accounts/clients','/manage/staff/trainers','/manage/staff/workers','/manage/staff-operations/schedules','/manage/staff-operations/attendance','/manage/memberships','/manage/membership-plans','/manage/finance','/manage/fitness-plans/workouts','/manage/fitness-plans/diets','/manage/fitness-plans/workouts/create','/manage/fitness-plans/diets/create','/messages','/notifications','/gym-settings','/audit-logs','/manage/reports'] as $uri)status('GET',$uri,[],$owner,200,'Owner view '.$uri,true);
    foreach(['/client/dashboard','/client/account-profile','/client/memberships','/client/plans','/client/payments','/client/progress','/client/ai','/messages','/notifications'] as $uri)status('GET',$uri,[],$client,200,'Client view '.$uri,true);
    foreach(['/owner/dashboard','/manager/dashboard','/gym-settings','/manage/accounts/clients','/manage/finance'] as $uri)status('GET',$uri,[],$client,403,'Client management denial '.$uri);
    status('GET','/client/dashboard',[],$owner,403,'Owner cannot enter Client portal');
    $trainerData=['name'=>'Fixture Trainer','email'=>'trainer-'.uniqid().'@example.test','phone'=>'123456789','is_active'=>true,'specialties'=>'Strength','qualifications'=>'Certified'];
    status('POST','/manage/staff/trainers',$trainerData,$manager,302,'Approved Manager creates Trainer');$trainer=Trainer::where('email',$trainerData['email'])->firstOrFail();
    $workerData=['name'=>'Fixture Worker','email'=>'worker-'.uniqid().'@example.test','is_active'=>true,'job_title'=>'Reception'];
    status('POST','/manage/staff/workers',$workerData,$manager,302,'Approved Manager creates Worker');$worker=Worker::where('email',$workerData['email'])->firstOrFail();
    status('PUT','/manage/staff/trainers/'.$trainer->id,[...$trainerData,'name'=>'Updated Trainer'],$manager,302,'Trainer update');
    status('GET','/manage/staff/trainers/'.$trainer->id.'/edit',[],$owner,200,'Trainer edit view',true);
    $scheduleData=['staff_key'=>'trainer:'.$trainer->id,'starts_at'=>\App\Support\GymClock::today()->format('Y-m-d').'T08:00','ends_at'=>\App\Support\GymClock::today()->format('Y-m-d').'T10:00'];
    status('POST','/manage/schedules',$scheduleData,$manager,302,'Schedule create');$schedule=StaffSchedule::where('trainer_id',$trainer->id)->firstOrFail();
    status('POST','/manage/schedules',$scheduleData,$manager,422,'Overlapping schedule rejected');
    status('PUT','/manage/schedules/'.$schedule->id,[...$scheduleData,'ends_at'=>\App\Support\GymClock::today()->format('Y-m-d').'T11:00'],$manager,302,'Schedule edit');
    $attendance=['staff_key'=>'trainer:'.$trainer->id,'staff_schedule_id'=>$schedule->id,'attendance_date'=>\App\Support\GymClock::today()->toDateString(),'status'=>'present','checked_in_at'=>\App\Support\GymClock::today()->format('Y-m-d').'T08:00','checked_out_at'=>\App\Support\GymClock::today()->format('Y-m-d').'T10:00'];
    status('POST','/manage/attendance',$attendance,$manager,302,'Attendance create');status('POST','/manage/attendance',[...$attendance,'status'=>'late'],$manager,302,'Attendance correction');
    check(StaffAttendance::where('trainer_id',$trainer->id)->count()===1,'Attendance correction avoids duplicates');
    status('POST','/manage/attendance',[...$attendance,'staff_key'=>'worker:'.$worker->id],$manager,422,'Attendance rejects wrong schedule staff');
    status('DELETE','/manage/schedules/'.$schedule->id,[],$manager,422,'Schedule with attendance cannot be deleted');
    status('DELETE','/manage/staff/trainers/'.$trainer->id,[],$manager,302,'Trainer archive');
    check(StaffSchedule::find($schedule->id)->trainer?->id===$trainer->id,'Archived Trainer history relationship retained');
    $attendancePage=status('GET','/manage/staff-operations/attendance',[],$owner,200,'Archived staff attendance correction form',true);
    check(str_contains($attendancePage['text'],'<option value="trainer:'.$trainer->id.'"'),'Archived Trainer remains selectable for attendance corrections');
    status('PUT','/manage/staff/trainers/'.$trainer->id,$trainerData,$owner,302,'Trainer restoration');
    $manager->managerPermissions()->where('permission','trainers.manage')->delete();
    status('POST','/manage/staff/trainers',[...$trainerData,'email'=>'new-'.uniqid().'@example.test'],$manager,403,'Permission revocation blocks writes immediately');
    status('GET','/manage/staff/trainers',[],$manager,403,'Permission revocation blocks reads immediately');
    $planData=['name'=>'Fixture Monthly','duration_days'=>30,'price'=>'100.00','currency'=>'PKR','is_active'=>true];
    status('POST','/manage/membership-plans',$planData,$owner,302,'Membership plan create');$plan=MembershipPlan::where('name',$planData['name'])->firstOrFail();
    $membershipData=['user_id'=>$client->id,'membership_plan_id'=>$plan->id,'starts_on'=>\App\Support\GymClock::today()->toDateString()];
    status('POST','/manage/memberships',$membershipData,$owner,302,'Membership create');$membership=Membership::where('user_id',$client->id)->firstOrFail();
    check($membership->ends_on->eq(\App\Support\GymClock::today()->addDays(29))&&$membership->agreed_price==='100.00','Membership term and price snapshot');
    status('POST','/manage/memberships',$membershipData,$owner,422,'Overlapping membership rejected');
    status('PUT','/manage/membership-plans/'.$plan->id,[...$planData,'name'=>'Changed Monthly','price'=>'150.00'],$owner,302,'Membership plan edit');
    check($membership->fresh()->plan_name==='Fixture Monthly'&&$membership->fresh()->agreed_price==='100.00','Membership history survives price and name changes');
    status('POST','/manage/memberships/'.$membership->id.'/renew',[...$membershipData,'starts_on'=>\App\Support\GymClock::today()->addDays(30)->toDateString()],$owner,302,'Membership renewal creates next term');
    check(Membership::where('user_id',$client->id)->count()===2,'Renewal preserves original membership');
    status('POST','/manage/memberships/'.$membership->id.'/renew',[...$membershipData,'user_id'=>$other->id],$owner,422,'Renewal cannot change Client');
    $paymentData=['user_id'=>$client->id,'membership_id'=>$membership->id,'amount'=>'100.00','currency'=>'PKR','method'=>'cash','status'=>'paid'];
    status('POST','/manage/payments',$paymentData,$owner,302,'Payment recorded');$payment=Payment::where('membership_id',$membership->id)->firstOrFail();
    status('POST','/manage/payments',$paymentData,$owner,422,'Membership overpayment rejected');
    status('POST','/manage/payments',[...$paymentData,'user_id'=>$other->id],$owner,422,'Payment cannot use another Client membership');
    status('POST','/manage/payments',[...$paymentData,'currency'=>'USD'],$owner,422,'Payment currency mismatch rejected');
    status('PUT','/manage/payments/'.$payment->id.'/status',['status'=>'void'],$owner,422,'Settled payment immutable');
    status('POST','/manage/payments/'.$payment->id.'/refund',['amount'=>'25.00','reason'=>'Correction'],$owner,302,'Partial refund');
    status('POST','/manage/payments/'.$payment->id.'/refund',['amount'=>'80.00','reason'=>'Over refund'],$owner,422,'Over-refund rejected');
    check($payment->fresh()->amount==='100.00'&&$payment->refunds()->sum('amount')==='25.00','Refund preserves original payment');
    status('POST','/manage/expenses',['description'=>'Fixture expense','category'=>'Operations','amount'=>'200.00','currency'=>'PKR','incurred_on'=>\App\Support\GymClock::today()->toDateString()],$owner,302,'Expense recorded');
    $report=status('GET','/manage/reports?months=3&currency=PKR',[],$owner,200,'Database financial report');
    $last=$report['json']['rows'][array_key_last($report['json']['rows'])];check($last['revenue']===75&&$last['expenses']===200&&$last['net']===-125,'Real report totals and negative net');
    $manager->managerPermissions()->whereIn('permission',['finance.view','finance.manage'])->delete();
    $report=status('GET','/manage/reports',[],$manager,200,'Operational-only Manager report');check(!isset($report['json']['rows'][0]['revenue']),'Operational report hides finance');
    status('POST','/manage/payments',$paymentData,$manager,403,'Unapproved Manager cannot record payments');
        check($membership->fresh()->net_paid===75.0,'Membership balance accounts for completed refunds');
    status('PUT','/manage/accounts/clients/'.$client->id,['name'=>'Managed Client','email'=>$client->email,'is_active'=>true,'phone'=>'123456789','address'=>'Gym client address'],$owner,302,'Owner manages Client contact records');
    check($client->fresh()->clientProfile->address==='Gym client address','Managed Client profile persisted');
    status('POST','/manage/memberships/'.$membership->id.'/cancel',[],$owner,302,'Membership cancellation');
    check($membership->fresh()->status==='cancelled'&&Payment::find($payment->id)!==null,'Cancelled membership keeps payment history');
    status('DELETE','/manage/membership-plans/'.$plan->id,[],$owner,302,'Membership plan archive');
    check($membership->fresh()->plan?->id===$plan->id,'Archived membership plan stays readable');
    $exercise=Exercise::firstOrFail();$workoutData=['title'=>'Fixture Workout','description'=>'Personal strength plan','is_active'=>true,'exercises'=>[$exercise->id]];
    status('POST','/manage/fitness-plans/workouts',$workoutData,$owner,302,'Workout plan create');$workout=WorkoutPlan::where('title','Fixture Workout')->firstOrFail();
    status('PUT','/manage/fitness-plans/workouts/'.$workout->id,[...$workoutData,'title'=>'Updated Workout'],$owner,302,'Workout plan edit');
    status('GET','/manage/fitness-plans/workouts/'.$workout->id.'/edit',[],$owner,200,'Workout edit view',true);
    $dietData=['title'=>'Fixture Diet','is_active'=>true,'image'=>DietPlan::first()->image,'breakfast'=>'Oats','lunch'=>'Chicken','dinner'=>'Fish','snacks'=>'Fruit','benefits'=>'Balanced','tips'=>'Hydrate'];
    status('POST','/manage/fitness-plans/diets',$dietData,$owner,302,'Diet plan create');$diet=DietPlan::where('title','Fixture Diet')->firstOrFail();
    status('PUT','/manage/fitness-plans/diets/'.$diet->id,[...$dietData,'title'=>'Updated Diet'],$owner,302,'Diet plan edit');
    status('GET','/manage/fitness-plans/diets/'.$diet->id.'/edit',[],$owner,200,'Diet edit view',true);
    foreach(['workouts'=>$workout,'diets'=>$diet] as $type=>$fitnessPlan)status('POST','/manage/assignments/'.$type,['user_id'=>$client->id,'plan_id'=>$fitnessPlan->id,'starts_on'=>\App\Support\GymClock::today()->toDateString(),'notes'=>'Private Alpha instructions'],$owner,302,'Assign '.$type);
    status('POST','/manage/assignments/workouts',['user_id'=>$client->id,'plan_id'=>$workout->id,'starts_on'=>\App\Support\GymClock::today()->addDays(30)->toDateString()],$owner,302,'Future assignment');
    $future=ClientPlanAssignment::where('user_id',$client->id)->latest('id')->first();
    status('PUT','/manage/assignments/workouts/'.$future->id.'/end',[],$owner,302,'End future assignment');check($future->fresh()->cancelled_at!==null,'Future assignment marked ended without deleting history');
    $ownPlans=status('GET','/client/plans',[],$client,200,'Assigned plans visible',true);check(str_contains($ownPlans['text'],'Private Alpha instructions'),'Client sees own plan instructions');
    $otherPlans=status('GET','/client/plans',[],$other,200,'Other Client plans',true);check(!str_contains($otherPlans['text'],'Private Alpha instructions'),'Client plan isolation');
    status('DELETE','/manage/fitness-plans/workouts/'.$workout->id,[],$owner,302,'Workout archive');
    check(ClientPlanAssignment::where('workout_plan_id',$workout->id)->first()->workoutPlan!==null,'Archived workout assignment remains readable');
    $progress=['recorded_on'=>\App\Support\GymClock::today()->toDateString(),'weight_kg'=>70,'height_cm'=>175,'consent'=>true,'notes'=>'Private Alpha progress'];
    status('POST','/client/progress',[...$progress,'consent'=>false],$client,422,'Progress requires consent');
    status('POST','/client/progress',$progress,$client,200,'Client progress save');$entry=FitnessProgress::where('user_id',$client->id)->firstOrFail();
    check((float)$entry->bmi===22.86&&$client->fresh()->clientProfile->fitness_data_consent_at!==null,'Server calculates BMI and records consent');
    status('POST','/client/progress',[...$progress,'weight_kg'=>72,'user_id'=>$other->id],$client,200,'Progress owner set server-side');
    check(FitnessProgress::where('user_id',$client->id)->count()===1&&FitnessProgress::where('user_id',$other->id)->count()===0,'Progress update is personal and duplicate-safe');
    status('DELETE','/client/progress/'.$entry->id,[],$other,403,'Progress ID isolation');
    $profile=['name'=>'Alpha Updated','phone'=>'123456789','fitness_consent'=>true,'fitness_goal'=>'Build strength'];status('PUT','/client/account-profile',$profile,$client,302,'Client profile update');
    $paymentView=status('GET','/client/payments',[],$other,200,'Other Client payment page',true);check(!str_contains($paymentView['text'],$payment->receipt_number),'Client payment isolation');
    $contact=['fullName'=>'Fixture Contact','email'=>'contact-'.uniqid().'@example.test','phone'=>'123456789','subject'=>'Membership question','message'=>'Please tell me about membership renewal.'];
    status('POST','/api/contact',$contact,$client,200,'Contact persists in database');$inquiry=Inquiry::where('email',$contact['email'])->firstOrFail();check($inquiry->user_id===$client->id,'Signed-in Client inquiry ownership');
    status('POST','/api/contact',['email'=>'invalid'],null,422,'Contact backend validation');
    $newsletter=['email'=>'newsletter-'.uniqid().'@example.test','consent'=>true];status('POST','/api/newsletter',$newsletter,null,200,'Newsletter stored');status('POST','/api/newsletter',$newsletter,null,200,'Repeat newsletter accepted');check(Inquiry::where('newsletter_email',$newsletter['email'])->count()===1,'Newsletter duplicates prevented');
    status('PUT','/inquiries/'.$inquiry->id,['status'=>'resolved','reply'=>'Your renewal is available at reception.'],$owner,302,'Inquiry reply');check($inquiry->fresh()->reply!==null&&count(Storage::disk('local')->files('.inquiry-replies'))>=1,'Inquiry reply and private local email preview');
    $messageData=['recipient_id'=>$owner->id,'subject'=>'Private Alpha message','body'=>'I would like to renew my membership.'];status('POST','/messages',$messageData,$client,302,'Client sends gym message');$message=Message::where('sender_id',$client->id)->firstOrFail();
    status('POST','/messages',[...$messageData,'recipient_id'=>$other->id],$client,404,'Client cannot message another Client');
    status('PUT','/messages/'.$message->id.'/read',[],$other,403,'Message ID isolation');status('PUT','/messages/'.$message->id.'/read',[],$owner,302,'Owner marks message read');
    $ownMessages=status('GET','/messages',[],$other,200,'Other Client messages',true);check(!str_contains($ownMessages['text'],'Private Alpha message')&&!str_contains($ownMessages['text'],'Your renewal is available'),'Messages and inquiries isolated');
    status('POST','/notifications',['title'=>'Gym notice','body'=>'Fixture broadcast','audience'=>'client'],$manager,403,'Only Owner broadcasts');
    status('POST','/notifications',['title'=>'Gym notice','body'=>'Fixture notice','audience'=>'manager','recipient_id'=>$client->id],$owner,422,'Notification recipient must match selected audience');
    status('POST','/notifications',['title'=>'Gym notice','body'=>'Fixture notice','audience'=>'client','recipient_id'=>2147483647],$owner,422,'Unknown notification recipient rejected');
    status('POST','/notifications',['title'=>'Gym notice','body'=>'Fixture broadcast','audience'=>'client'],$owner,302,'Owner broadcasts notifications');$notice=$client->notifications()->latest()->first();
    status('PUT','/notifications/'.$notice->id.'/read',[],$other,404,'Notification ID isolation');status('PUT','/notifications/'.$notice->id.'/read',[],$client,302,'Client reads own notification');
    Http::fake(['*'=>Http::response(['choices'=>[['message'=>['content'=>'A verified fixture reply.']]]],200)]);
    status('POST','/client/ai',['message'=>'Hello','consent'=>false],$client,422,'AI history requires consent');
    $chat=status('POST','/client/ai',['message'=>'A strength question','consent'=>true],$client,200,'AI conversation saved');$conversation=AiConversation::findOrFail($chat['json']['conversation_id']);
    check($conversation->user_id===$client->id&&$conversation->messages()->count()===2,'AI user/assistant pair and ownership');
    status('POST','/client/ai',['message'=>'Continue','consent'=>true,'conversation_id'=>$conversation->id],$other,404,'AI conversation ID isolation');
    status('GET','/client/ai/'.$conversation->id,[],$other,403,'AI history display isolation');
    status('DELETE','/client/ai/'.$conversation->id,[],$other,403,'AI history deletion isolation');
    status('GET','/client/ai/'.$conversation->id,[],$client,200,'AI stored history view',true);
    status('POST','/client/ai',['message'=>'Continue','consent'=>true,'conversation_id'=>$conversation->id],$client,200,'Continue saved AI conversation');
    check($conversation->messages()->count()===4,'AI subsequent message pair saved');
    config(['services.fusion_ai.api_key'=>'']);
    $fallback=status('POST','/client/ai',['message'=>'Hello','consent'=>true],$client,200,'Local AI fallback stored');check($fallback['json']['source']==='local','Fallback source format retained');
    status('DELETE','/client/ai/'.$conversation->id,[],$client,302,'Own AI conversation removal');check($conversation->messages()->count()===0&&$conversation->fresh()->trashed(),'Personal AI contents removed');
    foreach(['/index.html','/about.html','/dietplans.html','/workouts.html','/BMI.html','/fusion-ai.html','/contact.html','/membership-plans'] as $uri)status('GET',$uri,[],null,200,'Public page '.$uri,true);
    $workouts=status('GET','/api/workouts',[],null,200,'Database workout endpoint');check(count($workouts['json']['categories'])===7,'Seven workout categories');
    $exerciseCount=0;foreach($workouts['json']['categories'] as $category)$exerciseCount+=count($category['exercises']);check($exerciseCount===57,'All 57 exercises preserved');
    $diets=status('GET','/api/diets',[],null,200,'Database diet endpoint');check(count($diets['json']['diets'])>=8,'Original eight diets present');
    status('GET','/api/config',[],null,200,'Laravel WhatsApp configuration');
    $settings=App\Models\GymSetting::findOrFail(1);status('PUT','/gym-settings',['name'=>'Fixture Gym','email'=>'gym@example.test','phone'=>'923001234567','timezone'=>'Asia/Karachi','currency'=>'PKR','opening_hours'=>'{"Monday":"06:00â€“22:00"}'],$owner,302,'Owner gym settings update');
    check(App\Models\GymSetting::find(1)->name==='Fixture Gym','Gym settings persisted within test transaction');
    $manager->is_active=false;$manager->save();status('GET','/manager/dashboard',[],$manager,403,'Inactive Manager session blocked');
    status('POST','/login',['email'=>$manager->email,'password'=>'FixtureSecure123!'],null,422,'Inactive Manager login blocked');
        $managerData['permissions']=['workers.manage'];$managerData['is_active']=true;
    status('PUT','/manage/accounts/managers/'.$manager->id,$managerData,$owner,302,'Owner edits Manager permissions through dashboard controller');
    status('GET','/manage/staff/workers',[],$manager,200,'New Manager grant takes effect',true);
    status('GET','/manage/memberships',[],$manager,403,'Removed Manager grant takes effect');
    status('GET','/manage/accounts/managers/'.$manager->id.'/edit',[],$owner,200,'Manager permission edit form',true);
    status('GET','/manage/accounts/clients/'.$client->id.'/edit',[],$owner,200,'Client contact edit form',true);
    check(AuditLog::where('user_id',$owner->id)->where('action','payment.recorded')->exists(),'Management actions audited');
} catch(Throwable $error) { $failure=$error instanceof RuntimeException?$error->getMessage():get_class($error).' at '.basename($error->getFile()).':'.$error->getLine(); }
finally {while(DB::transactionLevel()>0)DB::rollBack();}
$preserved=$baseline===snapshot();$report=['passed'=>$failure===null&&$preserved,'checks'=>count($checks),'failure'=>$failure,'databasePreserved'=>$preserved,'checksExecuted'=>$checks];
file_put_contents($folder.'/management-tests.json',json_encode($report,JSON_PRETTY_PRINT));
echo json_encode(array_diff_key($report,['checksExecuted'=>true]),JSON_PRETTY_PRINT),PHP_EOL;
exit($report['passed']?0:1);





