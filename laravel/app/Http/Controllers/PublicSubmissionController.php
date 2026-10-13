<?php
namespace App\Http\Controllers;
use App\Http\Requests\ContactRequest;
use App\Models\Inquiry;
use Illuminate\Http\Request;
class PublicSubmissionController extends Controller  {
    public function contact(ContactRequest $request)  {
        Inquiry::create(['name'=>$request->fullName,...$request->safe()->only(['email','phone','subject','message']),
        'user_id'=>$request->user()?->role==='client'?$request->user()->id:null,'kind'=>'contact']);
        return response()->json(['message'=>'Your message has been received.']);
    }
    public function newsletter(Request $request)  {
        $data=$request->validate(['email'=>['required','email','max:254'],'consent'=>['accepted']]);
        $email=mb_strtolower(trim($data['email']));
        Inquiry::firstOrCreate(['newsletter_email'=>$email],[
        'kind'=>'newsletter','name'=>'Newsletter subscriber','email'=>$email,
        'subject'=>'Newsletter subscription','message'=>'Opted in to gym newsletter updates.']);
        // A neutral response does not reveal whether an address was already subscribed.
        return response()->json(['message'=>'Your newsletter subscription has been received.']);
    }
}
