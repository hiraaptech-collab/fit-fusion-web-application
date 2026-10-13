<?php
namespace App\Services;

use App\Models\AuditLog;
use App\Models\GymSetting;
use Illuminate\Support\Facades\{Auth,Cache,DB};
use Illuminate\Contracts\Cache\LockTimeoutException;

class AiUsageService
{
    public function reserve(array $messages): array
    {
        $settings=config('services.fusion_ai');
        $bytes=strlen(file_get_contents(resource_path('prompts/fusion-ai.txt')));
        foreach($messages as $message)$bytes+=strlen($message['content'])+32;
        // Conservative reservation; actual provider usage is recorded separately.
        $tokens=$bytes+1024+64;
        $slot=null;
        for($index=0;$index<$settings['concurrent_requests'];$index++){
            $candidate=Cache::lock('ai-provider-slot:'.$index,60);
            if($candidate->get()){$slot=$candidate;break;}
        }
        if(!$slot)return $this->limited('Fusion-AI is busy. Please try again shortly.');
        try {
            $record=DB::transaction(function()use($settings,$tokens){
                GymSetting::whereKey(1)->lockForUpdate()->firstOrFail();
                $minuteKey='ai-budget:'.gmdate('YmdHi');
                $minute=Cache::get($minuteKey,['requests'=>0,'tokens'=>0]);
                if($minute['requests']>=$settings['global_requests_per_minute']||$minute['tokens']+$tokens>$settings['global_tokens_per_minute'])return null;
                
                    // One gym settings row serializes budget reservations across workers.

                    $daily=AuditLog::where('action','ai.provider')->where('created_at','>=',now()->startOfDay());
                    $totals=(clone $daily)->selectRaw("COUNT(*) AS requests, COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.reserved_tokens')) AS UNSIGNED)),0) AS tokens")->first();
                    $user=Auth::user();$ip=app('request')->ip();
                    $account=(clone $daily)->when($user,fn($q)=>$q->where('user_id',$user->id),fn($q)=>$q->whereNull('user_id')->where('ip_address',$ip))->count();
                    $accountLimit=$user?$settings['user_requests_per_day']:$settings['guest_requests_per_day'];
                    if($totals->requests>=$settings['global_requests_per_day']||$totals->tokens+$tokens>$settings['global_tokens_per_day']||$account>=$accountLimit)return null;
                    $record=AuditLog::create(['user_id'=>$user?->id,'action'=>'ai.provider','ip_address'=>$ip,'metadata'=>['state'=>'reserved','reserved_tokens'=>$tokens,'model'=>$settings['model']]]);
                    Cache::put($minuteKey,['requests'=>$minute['requests']+1,'tokens'=>$minute['tokens']+$tokens],120);
                    return $record;
            },3);
            if(!$record){$slot->release();return $this->limited('The gym\'s AI usage limit has been reached. Please try again later.');}
            return ['record'=>$record,'slot'=>$slot,'started'=>microtime(true)];
        }catch(LockTimeoutException $exception){$slot->release();return $this->limited('Fusion-AI is busy. Please try again shortly.');}
        catch(\Throwable $exception){$slot->release();throw $exception;}
    }

    public function complete(array $reservation,?array $result,array $usage): void
    {
        try {
            $record=$reservation['record'];
            $metadata=$record->metadata;
            $metadata+=['status'=>$result['status']??500,'latency_ms'=>(int)round((microtime(true)-$reservation['started'])*1000)];
            $metadata['state']=($result===null||$result['status']!==200)?'failed':(($result['body']['source']??'provider')==='local'?'fallback':'completed');
            foreach(['prompt_tokens','completion_tokens','total_tokens'] as $field){
                if(isset($usage[$field])&&is_numeric($usage[$field]))$metadata[$field]=max(0,min(1000000,(int)$usage[$field]));
            }
            $record->update(['metadata'=>$metadata]);
        }finally{$reservation['slot']->release();}
    }

    private function limited(string $message): array
    {
        return ['error'=>['status'=>429,'body'=>['error'=>$message,'code'=>'RATE_LIMITED']]];
    }
}

