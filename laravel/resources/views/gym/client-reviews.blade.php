@extends('layouts.dashboard')
@section('title','My feedback & reviews')
@section('description','Share your experience and follow the status of your reviews.')
@section('head')<link rel="stylesheet" href="{{ asset('assets/reviews.css') }}">@endsection
@section('content')
<div class="dash-card" id="write-review"><h2>How has your experience been?</h2><p>Reviews are published after approval. Choose a public display name and keep contact details and private health information out of your review.</p>
@if($canSubmit)
<form method="POST" action="{{ route('client.reviews.store') }}" class="dash-form" id="reviewForm">@csrf
<div class="dash-field"><label for="displayName">Public display name</label><input id="displayName" name="display_name" value="{{ old('display_name') }}" placeholder="e.g. Ali K." minlength="2" maxlength="40" required autocomplete="off"></div>
<fieldset class="review-rating full"><legend>Your rating</legend><div class="review-rating-options">@for($star=1;$star<=5;$star++)<label><input type="radio" name="rating" value="{{ $star }}" @checked((string)old('rating')===(string)$star) required><span><i class="bi bi-star-fill" aria-hidden="true"></i> {{ $star }}<span class="visually-hidden"> {{ $star===1?'star':'stars' }}</span></span></label>@endfor</div></fieldset>
<div class="dash-field full"><label for="reviewMessage">Your review</label><textarea id="reviewMessage" name="message" rows="5" minlength="20" maxlength="2000" required placeholder="Tell us about your experience at the gym.">{{ old('message') }}</textarea><small>20–2,000 characters. One review every 30 days; repeated reviews are not accepted.</small></div>
<label class="dash-check full"><input type="checkbox" name="public_consent" value="1" @checked(old('public_consent')) required> I agree that my display name, rating, review and submission date may be published after approval.</label>
<div class="full dash-actions"><button class="dash-button" type="submit">Submit review</button><a class="dash-button secondary" href="{{ route('public.feedback') }}">Read public reviews</a></div></form>
@else
<div class="dash-alert">Your next review can be submitted on {{ \App\Support\GymClock::display($nextSubmission,'d M Y H:i') }}. Your existing submissions are listed below.</div><a class="dash-button secondary" href="{{ route('public.feedback') }}">Read public reviews</a>
@endif</div>
<h2 class="mt-4">Your review history</h2><div class="reviews-grid personal-reviews">
@forelse($reviews as $review)<article class="review-card"><div class="review-card-top"><h3>{{ $review->display_name }}</h3><span class="review-status {{ $review->status }}">{{ ucfirst($review->status) }}</span></div><div class="review-stars" aria-label="{{ $review->rating }} out of 5 stars">@for($star=1;$star<=5;$star++)<i aria-hidden="true" class="bi {{ $star<=$review->rating?'bi-star-fill':'bi-star' }}"></i>@endfor</div><p class="review-message">{{ $review->message }}</p><time datetime="{{ $review->created_at->toIso8601String() }}">Submitted {{ \App\Support\GymClock::display($review->created_at,'d M Y H:i') }}</time>@if($review->status==='pending')<small>Waiting for approval.</small>@elseif($review->status==='approved')<small>Visible on the public Feedback page.</small>@else<small>Kept in your history; not displayed publicly.</small>@endif</article>
@empty<div class="dash-card dash-empty">You haven't submitted a review yet.</div>@endforelse</div><div class="dash-pagination">{{ $reviews->links('pagination::bootstrap-5') }}</div>
@endsection
