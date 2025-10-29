@extends('layouts.app')

@section('content')
    <h1>Payment #{{ $payment->id }}</h1>
    <p><strong>Invoice:</strong> <a href="{{ route('invoices.show', $payment->invoice) }}">{{ $payment->invoice->invoice_number }}</a></p>
    <p><strong>Amount:</strong> {{ $payment->amount }}</p>
    <p><strong>Method:</strong> {{ $payment->payment_method }}</p>
    <p><strong>Status:</strong> {{ $payment->status }}</p>

    <a href="{{ route('payments.index') }}" class="btn btn-secondary">Back</a>
@endsection
