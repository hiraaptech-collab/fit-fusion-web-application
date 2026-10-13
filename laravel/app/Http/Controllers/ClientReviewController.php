<?php
namespace App\Http\Controllers;

use App\Http\Requests\ClientReviewRequest;
use App\Models\ClientReview;
use App\Models\User;
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientReviewController extends Controller
{
    public function publicIndex()
    {
        $approved = ClientReview::where('status', 'approved');
        $total = (clone $approved)->count();
        $average = $total ? (float) (clone $approved)->avg('rating') : null;
        // Public views receive only the fields a Client agreed to publish.
        $reviews = $approved->select('display_name', 'rating', 'message', 'created_at')
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(12);
        return view('pages.feedback', compact('reviews', 'total', 'average'));
    }

    public function index(Request $request)
    {
        $reviews = $request->user()->reviews()->latest()->orderByDesc('id')->paginate(10);
        $latest = $request->user()->reviews()->latest()->first();
        $nextSubmission = $latest?->created_at->copy()->addDays(30);
        $canSubmit = !$nextSubmission || !$nextSubmission->isFuture();
        return view('gym.client-reviews', compact('reviews', 'nextSubmission', 'canSubmit'));
    }

    public function store(ClientReviewRequest $request)
    {
        DB::transaction(function () use ($request) {
            // Lock the Client so simultaneous requests cannot bypass the cooldown.
            $client = User::where('role', 'client')->where('is_active', true)
                ->lockForUpdate()->findOrFail($request->user()->id);
            $hash = ClientReview::messageHash($request->validated('message'));
            if ($client->reviews()->where('review_hash', $hash)->exists()) {
                throw ValidationException::withMessages(['message' => 'You have already submitted this review.']);
            }
            if ($client->reviews()->where('created_at', '>', now()->subDays(30))->exists()) {
                throw ValidationException::withMessages(['message' => 'Please allow 30 days between reviews. You can view your existing review below.']);
            }
            $review = $client->reviews()->create([
                ...$request->safe()->only(['display_name', 'rating', 'message']),
                'review_hash' => $hash, 'status' => 'pending',
            ]);
            GymActivity::record('feedback.submitted', $review);
            foreach (User::where('role', 'owner')->where('is_active', true)->get() as $owner) {
                GymActivity::notify($owner, 'New Client feedback', 'A review is waiting for approval in Feedback Management.');
            }
        });
        return redirect()->route('client.reviews')->with('status', 'Thank you! Your review is pending approval.');
    }
}
