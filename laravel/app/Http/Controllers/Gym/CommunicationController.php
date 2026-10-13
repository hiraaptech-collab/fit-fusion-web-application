<?php
namespace App\Http\Controllers\Gym;
use App\Http\Controllers\Controller;
use App\Models\{User,Message,Inquiry};
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Gate,Mail};
use Illuminate\Support\Str;
class CommunicationController extends Controller {
    public function index(Request $request) {
        $user=$request->user();
        $management=$user->hasGymPermission('messages.manage');
        abort_unless($management||$user->role==='client',403);
        $messages=Message::with(['sender','recipient'])->when(!$management,fn($q)=>$q->where(fn($q)=>$q->where('sender_id',$user->id)->orWhere('recipient_id',$user->id)))->latest()->paginate(15,['*'],'messages_page');
        $inquiries=Inquiry::when(!$management,fn($q)=>$q->where('user_id',$user->id))->when($request->filled('status'),fn($q)=>$q->where('status',$request->status))->latest()->paginate(15,['*'],'inquiries_page');
        $recipients=$management?User::where('role','client')->where('is_active',true)->get():User::where('role','owner')->get();
        return view('gym.messages',compact('messages','inquiries','recipients','management'));
    }
    public function send(Request $request) {
        $user=$request->user();
        $management=$user->hasGymPermission('messages.manage');
        abort_unless($management||$user->role==='client',403);
        $data=$request->validate(['recipient_id'=>['required','integer'],'subject'=>['nullable','string','max:255'],'body'=>['required','string','max:10000']]);
        $recipient=User::where('is_active',true)->where('role',$management?'client':'owner')->findOrFail($data['recipient_id']);
        DB::transaction(function() use($data,$user,$recipient) {
            $message=Message::create([...$data,'sender_id'=>$user->id]);GymActivity::record('message.sent',$message);
            GymActivity::notify($recipient,'New gym message',$message->subject?:'You have a new message.');
        }
        );
        return back()->with('status','Message sent.');
    }
    public function read(Message $message) {
        Gate::authorize('view',$message);
        abort_unless($message->recipient_id===auth()->id()||auth()->user()->hasGymPermission('messages.manage'),403);
        $message->update(['read_at'=>now()]);
        return back()->with('status','Message marked read.');
    }
    public function replyInquiry(Request $request,Inquiry $inquiry) {
        Gate::authorize('messages.manage');
        $data=$request->validate(['status'=>['required','in:new,in_progress,resolved'],'reply'=>['nullable','string','max:10000']]);
        DB::transaction(function() use($data,$inquiry) {
            $inquiry->update([...$data,'handled_by'=>auth()->id(),'resolved_at'=>$data['status']==='resolved'?now():null,'replied_at'=>empty($data['reply'])?null:now()]);
            if(!empty($data['reply'])&&$inquiry->user) GymActivity::notify($inquiry->user,'Inquiry response','The gym has replied to your inquiry.');
            GymActivity::record('inquiry.updated',$inquiry);
        }
        );
        if(!empty($data['reply'])) {
            if(app()->environment('local','testing')&&in_array(config('mail.default'),['array','log'],true)) {
                // Dot filenames keep local previews private through the original Node server.
                \Illuminate\Support\Facades\Storage::disk('local')->put('.inquiry-replies/.'.Str::uuid().'.json',json_encode(['to'=>$inquiry->email,'subject'=>'FitFusion: '.$inquiry->subject,'body'=>$data['reply']],JSON_PRETTY_PRINT));
                return back()->with('status','Reply saved. A private local email preview was created; no external email was sent.');
            }
            try {
                Mail::raw($data['reply'],fn($mail)=>$mail->to($inquiry->email)->subject('FitFusion: '.$inquiry->subject));
            }
            catch(\Throwable $exception) {
                return back()->withErrors(['reply'=>'Reply saved, but email delivery failed. Check your private SMTP configuration.']);
            }
        }
        return back()->with('status','Inquiry updated.');
    }
    public function notifications() {
        return view('gym.notifications',['notifications'=>auth()->user()->notifications()->latest()->paginate(20),'recipients'=>auth()->user()->role==='owner'?User::where('is_active',true)->get():collect()]);
    }
    public function markNotification(string $notification) {
        $notice=auth()->user()->notifications()->findOrFail($notification);
        $notice->markAsRead();
        return back()->with('status','Notification marked read.');
    }
    public function broadcast(Request $request) {
        abort_unless(auth()->user()->role==='owner',403);
        $data=$request->validate(['title'=>['required','string','max:150'],'body'=>['required','string','max:3000'],'recipient_id'=>['nullable','integer','exists:users,id'],'audience'=>['required','in:client,manager,all']]);
                if (!empty($data['recipient_id'])) {
            $recipient=User::where('is_active',true)->findOrFail($data['recipient_id']);
            if($data['audience']!=='all'&&$recipient->role!==$data['audience']) throw \Illuminate\Validation\ValidationException::withMessages(['recipient_id'=>'Choose a recipient in this audience, or select All.']);
        }
        DB::transaction(function() use($data) {
            $query=User::where('is_active',true)->when($data['audience']!=='all',fn($q)=>$q->where('role',$data['audience']));
            if(!empty($data['recipient_id']))$query->where('id',$data['recipient_id']);
            $query->chunkById(100,function($users) use($data) {
                foreach($users as $user)GymActivity::notify($user,$data['title'],$data['body']);
            }
            );
            GymActivity::record('notification.sent');
        }
        );
        return back()->with('status','Notifications sent.');
    }
}

