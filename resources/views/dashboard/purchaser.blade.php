@extends('layouts.app')

@section('content')
    <h1>Purchaser Dashboard</h1>

    <div class="row">
        <div class="col-md-8">
            <h4>Your Invoices</h4>
            <ul class="list-group">
                @foreach($pendingInvoices as $invoice)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                        <span>{{ $invoice->status }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="col-md-4">
            <div class="card mb-2"><div class="card-body">Total Unpaid: {{ $totalUnpaid ?? 0 }}</div></div>
            <div class="card"><div class="card-body">Total Paid: {{ $totalPaid ?? 0 }}</div></div>
        </div>
    </div>
@endsection
