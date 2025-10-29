@extends('layouts.app')

@section('content')
    <h1>Record Payment for {{ $invoice->invoice_number }}</h1>

    <form method="POST" action="{{ route('payments.store') }}">
        @csrf
        <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

        <div class="mb-3">
            <label class="form-label">Amount</label>
            <input name="amount" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-select">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="mobile_money">Mobile Money</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Payment Date</label>
            <input type="date" name="payment_date" class="form-control" required>
        </div>

        <button class="btn btn-primary">Record Payment</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Cancel</a>
    </form>
@endsection
