<?php
require __DIR__.'/../vendor/autoload.php';
$folder=__DIR__.'/../storage/app/private/production-audit';
if(!is_dir($folder))mkdir($folder,0700,true);
$credentialsFile=$folder.'/.database.json';
if(file_exists($credentialsFile))throw new RuntimeException('An audit database is already configured; do not overwrite it.');
$admin=new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$name='fitfusion_audit_'.gmdate('Ymd_His');$user='ff_audit_'.bin2hex(random_bytes(4));$password=bin2hex(random_bytes(24));
if($admin->query("SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=".$admin->quote($name))->fetchColumn())throw new RuntimeException('Test database already exists.');
$admin->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$admin->exec('CREATE USER '.$admin->quote($user)."@'localhost' IDENTIFIED BY ".$admin->quote($password));
$admin->exec('GRANT ALL PRIVILEGES ON `'.$name.'`.* TO '.$admin->quote($user)."@'localhost'");
$credentials=['database'=>$name,'username'=>$user,'password'=>$password,'app_key'=>'base64:'.base64_encode(random_bytes(32)),'client_password'=>'Aa1!'.bin2hex(random_bytes(12))];
file_put_contents($credentialsFile,json_encode($credentials));
foreach(['framework/cache/data','framework/sessions','framework/views','logs','app/private'] as $path)mkdir($folder.'/runtime/'.$path,0700,true);
$app=require __DIR__.'/audit-bootstrap.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate',['--force'=>true]);
$app->make(Illuminate\Contracts\Console\Kernel::class)->call('db:seed',['--class'=>'DatabaseSeeder','--force'=>true]);
$hash=Illuminate\Support\Facades\Hash::make($credentials['client_password']);
foreach(['owner','manager'] as $role){$person=new App\Models\User(['name'=>'Audit '.ucfirst($role),'email'=>$role.'@example.test','password'=>$hash]);$person->role=$role;$person->is_active=true;$person->save();if($role==='manager')foreach(array_keys(config('gym.manager_permissions')) as $permission)$person->managerPermissions()->create(['permission'=>$permission,'granted_by'=>1]);}
$plan=App\Models\MembershipPlan::create(['name'=>'Audit Monthly','slug'=>'audit-monthly','duration_days'=>30,'price'=>100,'currency'=>'PKR','is_active'=>true,'created_by'=>1]);
$workout=App\Models\WorkoutPlan::create(['title'=>'Audit training','slug'=>'audit-training','description'=>'Synthetic load-test plan.','is_active'=>true,'created_by'=>1]);
$workout->planExercises()->create(['exercise_id'=>App\Models\Exercise::first()->id,'sets'=>3,'reps'=>'10','sort_order'=>0]);
for($i=1;$i<=110;$i++){
 $client=new App\Models\User(['name'=>'Audit Client '.$i,'email'=>'client'.$i.'@example.test','password'=>$hash]);$client->role='client';$client->is_active=true;$client->save();$client->clientProfile()->create([]);
 $membership=$client->memberships()->create(['membership_plan_id'=>$plan->id,'plan_name'=>$plan->name,'duration_days'=>30,'agreed_price'=>100,'currency'=>'PKR','starts_on'=>now()->toDateString(),'ends_on'=>now()->addDays(29)->toDateString(),'status'=>'active','created_by'=>1]);
 $client->payments()->create(['membership_id'=>$membership->id,'receipt_number'=>'AUDIT-'.$i,'amount'=>50,'currency'=>'PKR','method'=>'cash','status'=>'paid','paid_at'=>now(),'recorded_by'=>1]);
 $client->planAssignments()->create(['workout_plan_id'=>$workout->id,'starts_on'=>now()->toDateString(),'assigned_by'=>1]);
}
echo json_encode(['prepared'=>true,'testDatabase'=>$name,'clients'=>110,'tables'=>count(Illuminate\Support\Facades\DB::select('SHOW TABLES')),'bcryptCost'=>config('hashing.bcrypt.rounds'),'realDataImported'=>false]),PHP_EOL;
