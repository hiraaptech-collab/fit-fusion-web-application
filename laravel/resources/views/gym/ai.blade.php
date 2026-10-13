@extends('layouts.dashboard')
@section('title','Fusion-AI')
@section('description','Your existing fitness coach, with personal conversation history.')
@section('head')<meta name="fitfusion-history-endpoint" content="{{ route('client.ai.send') }}"><meta name="fitfusion-history-required" content="1">@endsection
@section('content')
<div class="dash-card"><div class="dash-actions"><a class="dash-button secondary" href="{{ route('client.ai') }}">New conversation</a><label class="dash-check"><input type="checkbox" id="historyConsent" value="1" @checked($conversation)> I consent to storing this conversation in my personal history.</label></div><input type="hidden" id="historyConversationId" value="{{ $conversation?->id }}"></div>
@include('gym.partials.ai-panel')
<div class="dash-card"><h2>My conversation history</h2>@forelse($conversations as $item)<div class="dash-message"><div class="dash-actions"><a href="{{ route('client.ai.show',$item) }}">{{ $item->title }}</a><small>{{ \App\Support\GymClock::display($item->updated_at,'d M Y H:i') }}</small><form method="POST" action="{{ route('client.ai.delete',$item) }}" data-confirm="Remove this personal AI conversation and its messages?">@csrf @method('DELETE')<button class="dash-button danger">Remove</button></form></div></div>@empty<p class="dash-empty">No saved conversations yet.</p>@endforelse<div class="dash-pagination">{{ $conversations->links() }}</div></div>
@php($storedMessages = $history instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? collect($history->items())->reverse()->map(fn($message)=>$message->only(['role','content','source']))->values() : $history->map(fn($message)=>$message->only(['role','content','source']))->values())
@if($history instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $history->hasPages())<div class="dash-card dash-pagination"><small>Older messages remain available through the pages below.</small>{{ $history->withQueryString()->links() }}</div>@endif
<script type="application/json" id="storedAiHistory">@json($storedMessages)</script>
@endsection
@section('scripts')<script src="{{ asset('javascript/fusion-ai.js') }}"></script>@endsection
