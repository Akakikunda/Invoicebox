@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1>Invoices</h1>
        @if(auth()->user()->isProvider())
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">Create Invoice</a>
        @endif
    </div>

    <table class="table table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice</th>
                <th>Issue Date</th>
                <th>Due Date</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $invoice)
                <tr>
                    <td>{{ $invoice->id }}</td>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                    <td>{{ $invoice->issue_date->toDateString() }}</td>
                    <td>{{ $invoice->due_date->toDateString() }}</td>
                    <td>{{ $invoice->total }} {{ $invoice->currency }}</td>
                    <td>{{ $invoice->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>{{ $invoices->links() }}</div>
@endsection
