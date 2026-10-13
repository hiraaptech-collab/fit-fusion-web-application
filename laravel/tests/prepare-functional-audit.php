<?php
require __DIR__.'/../vendor/autoload.php';$folder=__DIR__.'/../storage/app/private/production-audit';
$file=$folder.'/.functional-database.json';if(file_exists($file))throw new RuntimeException('Functional test database already exists.');
$admin=new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$name='fitfusion_audit_verify_'.gmdate('Ymd_His');$user='ff_verify_'.bin2hex(random_bytes(4));$password=bin2hex(random_bytes(24));
$admin->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$admin->exec('CREATE USER '.$admin->quote($user)."@'localhost' IDENTIFIED BY ".$admin->quote($password));
$admin->exec('GRANT ALL PRIVILEGES ON `'.$name.'`.* TO '.$admin->quote($user)."@'localhost'");
$credentials=['database'=>$name,'username'=>$user,'password'=>$password,'app_key'=>'base64:'.base64_encode(random_bytes(32)),'client_password'=>'Aa1!'.bin2hex(random_bytes(12))];file_put_contents($file,json_encode($credentials));
foreach(['framework/cache/data','framework/sessions','framework/views','logs','app/private'] as $path)mkdir($folder.'/runtime-functional/'.$path,0700,true);
putenv('FITFUSION_AUDIT_MODE=functional');$app=require __DIR__.'/audit-bootstrap.php';$kernel=$app->make(Illuminate\Contracts\Console\Kernel::class);$kernel->call('migrate',['--force'=>true]);$kernel->call('db:seed',['--class'=>'DatabaseSeeder','--force'=>true]);
$owner=new App\Models\User(['name'=>'Synthetic audit Owner','email'=>'owner@example.test','password'=>$credentials['client_password']]);$owner->role='owner';$owner->is_active=true;$owner->save();
echo json_encode(['prepared'=>true,'functionalDatabase'=>$name,'realDataImported'=>false]),PHP_EOL;
