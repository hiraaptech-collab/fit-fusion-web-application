<?php
namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModerateReviewRequest;
use App\Models\ClientReview;
use App\Support\GymActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReviewManagementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('feedback.manage');
        $request->validate(['status' => ['nullable', 'in:pending,approved,rejected,hidden']]);
        $reviews = ClientReview::with('client:id,name,deleted_at')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->latest()->orderByDesc('id')->paginate(15)->withQueryString();
        $counts = ClientReview::selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        return view('gym.review-management', compact('reviews', 'counts'));
    }

    public function update(ModerateReviewRequest $request, ClientReview $review)
    {
        Gate::authorize('moderate', $review);
        DB::transaction(function () use ($request, $review) {
            $review = ClientReview::lockForUpdate()->findOrFail($review->id);
            $status = $request->validated('status');
            if ($review->status === $status) return;
            $review->update(['status' => $status, 'reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
            GymActivity::record('feedback.'.$status, $review);
            if ($review->client && !$review->client->trashed()) {
                GymActivity::notify($review->client, 'Feedback updated', 'Your review is now '.$status.'. View your feedback for details.');
            }
        });
        return back()->with('status', 'Feedback status updated. The original review remains in history.');
    }
}
