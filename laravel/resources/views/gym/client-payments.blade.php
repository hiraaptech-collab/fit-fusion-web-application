@extends('layouts.dashboard')
@section('title','My payment history')
@section('content')
<div class="dash-card"><div class="dash-table-wrap"><table class="dash-table"><thead><tr><th>Receipt</th><th>Amount</th><th>Status / method</th><th>Date</th><th>Refunds</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ $payment->receipt_number }}</td><td>{{ $payment->currency }} {{ $payment->amount }}</td><td>{{ $payment->status }}<small>{{ $payment->method }}</small></td><td>{{ \App\Support\GymClock::display($payment->paid_at,'d M Y') ?? '—' }}</td><td>@foreach($payment->refunds as $refund)<small>{{ $refund->currency }} {{ $refund->amount }} · {{ $refund->status }}</small>@endforeach</td></tr>@empty<tr><td colspan="5" class="dash-empty">No payments recorded.</td></tr>@endforelse</tbody></table></div><div class="dash-pagination">{{ $payments->links() }}</div></div>
@endsection
