<?php
$script=$argv[1]??'';
$allowed=['verify-database-auth.php','verify-gym-management.php','verify-client-reviews.php','verify-fusion-ai.php'];
if(!in_array($script,$allowed,true))throw new RuntimeException('Choose an approved audit test suite.');
putenv('FITFUSION_AUDIT_MODE=functional');$app=require __DIR__.'/audit-bootstrap.php';
// Legacy AI contract fixtures use synthetic requests and keys, never real Groq calls.
if($script==='verify-fusion-ai.php')foreach(['AI_ALLOW_GUEST_PROVIDER'=>'true','AI_GUEST_REQUESTS_PER_DAY'=>'10000','AI_GLOBAL_REQUESTS_PER_MINUTE'=>'10000','AI_GLOBAL_TOKENS_PER_MINUTE'=>'10000000','AI_GLOBAL_REQUESTS_PER_DAY'=>'10000','AI_GLOBAL_TOKENS_PER_DAY'=>'10000000'] as $key=>$value){putenv($key.'='.$value);$_ENV[$key]=$_SERVER[$key]=$value;}
require __DIR__.'/'.$script;
