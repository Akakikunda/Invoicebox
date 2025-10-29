@extends('layouts.app')

@section('content')
    <h1>Provider Dashboard</h1>

    <div class="row">
        <div class="col-md-8">
            <h4>Recent Invoices</h4>
            <ul class="list-group">
                @foreach($recentInvoices as $invoice)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                        <span>{{ $invoice->total_amount }} {{ $invoice->currency }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-md-4">
            <div class="card mb-2"><div class="card-body">Total Outstanding: {{ $stats['outstanding_amount'] ?? 0 }}</div></div>
<div class="card"><div class="card-body">Total Paid This Month: {{ $stats['paid_this_month'] ?? 0 }}</div></div>
        </div>
    </div>
@endsection
