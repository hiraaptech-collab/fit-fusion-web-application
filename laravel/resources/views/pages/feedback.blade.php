@extends('layouts.public')
@section('metadata')
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<base href="{{ url('/') }}/"><title>Client Feedback & Reviews | FitFusion</title>
<meta name="description" content="Read approved Client reviews and share your FitFusion gym experience.">
<link rel="stylesheet" href="{{ asset('assets/reviews.css') }}">
@endsection
@section('content')
<div id="my-navbar" role="navigation" aria-label="Main navigation"></div>
<section class="page-hero reviews-hero"><div class="container">
<span class="reviews-eyebrow">Our gym, through your eyes</span>
<h1 class="page-hero-title">Your experience.<br><span>Our inspiration.</span></h1>
<p class="page-hero-text">Honest feedback from our Clients, shared with care.</p>
<div class="reviews-hero-actions">
@if(!auth()->check() || auth()->user()->role==='client')
<a class="btn-primary-custom" id="writeReviewLink" href="{{ route('client.reviews') }}#write-review"><i class="bi bi-pencil-square"></i> Write a Review</a>
@elseif(auth()->user()->hasGymPermission('feedback.manage'))
<a class="btn-primary-custom" href="{{ route('gym.reviews') }}">Manage feedback</a>
@endif
<a class="reviews-text-link" href="#client-reviews">Explore reviews <i class="bi bi-arrow-down"></i></a>
</div></div></section>
<main class="reviews-section" id="client-reviews"><div class="container">
<div class="reviews-summary" aria-label="Approved review summary">
<div><span class="reviews-eyebrow">The Client perspective</span><h2>Small moments. Lasting progress.</h2><p>Every published review is approved by the gym. Only the display name chosen by the Client is shown.</p></div>
<div class="reviews-score"><strong id="reviewAverage">{{ $average===null?'—':number_format($average,1) }}</strong><span>/ 5</span><p id="reviewTotal">{{ number_format($total) }} approved {{ $total===1?'review':'reviews' }}</p>@if($average!==null)<div class="review-stars" aria-label="Average {{ number_format($average,1) }} out of 5 stars">@for($star=1;$star<=5;$star++)<i aria-hidden="true" class="bi {{ $average>=$star?'bi-star-fill':($average>=$star-0.5?'bi-star-half':'bi-star') }}"></i>@endfor</div>@endif</div>
</div>
<div class="reviews-grid">
@forelse($reviews as $review)
<article class="review-card" data-aos="fade-up"><div class="review-card-top"><span class="review-avatar" aria-hidden="true"><i class="bi bi-person"></i></span><div><h3>{{ $review->display_name }}</h3><time datetime="{{ $review->created_at->toIso8601String() }}">{{ \App\Support\GymClock::display($review->created_at,'d M Y') }}</time></div><i class="bi bi-quote review-quote" aria-hidden="true"></i></div>
<div class="review-stars" aria-label="{{ $review->rating }} out of 5 stars">@for($star=1;$star<=5;$star++)<i aria-hidden="true" class="bi {{ $star<=$review->rating?'bi-star-fill':'bi-star' }}"></i>@endfor</div><p class="review-message">{{ $review->message }}</p><span class="review-approved"><i class="bi bi-check-circle"></i> Approved Client review</span></article>
@empty
<div class="reviews-empty"><i class="bi bi-chat-heart" aria-hidden="true"></i><h3>Your story could be the first.</h3><p>Approved Client reviews will appear here. Share what your gym experience means to you.</p></div>
@endforelse
</div><div class="reviews-pagination">{{ $reviews->links('pagination::bootstrap-5') }}</div>
</div></main><div id="my-footer" role="contentinfo"></div>
@endsection
