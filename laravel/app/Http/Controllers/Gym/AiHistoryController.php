<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Services\FusionAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache,DB,Gate};
use Illuminate\Support\Str;
class AiHistoryController extends Controller {
    public function index(?AiConversation $conversation=null) {
        if($conversation)Gate::authorize('view',$conversation);
        return view('gym.ai',['conversations'=>auth()->user()->aiConversations()->latest('updated_at')->paginate(20),
        'conversation'=>$conversation,'history'=>$conversation?->messages()->latest('id')->paginate(50,['*'],'messages_page')??collect()]);
    }
    public function send(Request $request,FusionAiService $ai) {
        $data=$request->validate(['message'=>['required','string','max:1000',function($attribute,$value,$fail) {
            if(is_string($value)&&strlen(mb_convert_encoding($value,'UTF-16LE','UTF-8'))/2>1000)$fail('Message is too long. Please keep it under 1000 characters.');
        }
        ],'conversation_id'=>['nullable','integer'],'consent'=>['accepted']]);
        $conversation=empty($data['conversation_id'])?null:$request->user()->aiConversations()->findOrFail($data['conversation_id']);
        $lock=Cache::lock('client-ai:'.$request->user()->id.':'.($conversation?->id??'new'),60);
        if(!$lock->get())return response()->json(['error'=>'Please wait for your previous reply.'],409);
        try {
            $messages=$conversation?->messages()->latest('id')->limit(20)->get()->reverse()->map(fn($m)=>$m->only(['role','content']))->values()->all()??[];
            $messages[]=['role'=>'user','content'=>$data['message']];
            $result=$ai->reply($messages);
            if($result['status']!==200)return response()->json($result['body'],$result['status']);
            $conversation=DB::transaction(function() use($conversation,$request,$data,$result) {
                if($conversation)$conversation=$request->user()->aiConversations()->lockForUpdate()->findOrFail($conversation->id);
                else $conversation=$request->user()->aiConversations()->create(['title'=>Str::limit($data['message'],80),'consented_at'=>now()]);
                $conversation->messages()->create(['role'=>'user','content'=>$data['message']]);
                $conversation->messages()->create(['role'=>'assistant','content'=>$result['body']['reply'],'source'=>$result['body']['source']??'provider']);
                $conversation->touch();return $conversation;
            }
            );
            return response()->json([...$result['body'],'conversation_id'=>$conversation->id]);
        }
        finally {
            $lock->release();
        }
    }
    public function destroy(AiConversation $conversation) {
        Gate::authorize('update',$conversation);
        DB::transaction(function() use($conversation) {
            $conversation->update(['title'=>null]);$conversation->messages()->delete();$conversation->delete();
        }
        );
        return redirect()->route('client.ai')->with('status','Personal AI conversation removed.');
    }
}

