@extends('layouts.app')

@section('content')
    <h1>Invoice {{ $invoice->invoice_number }}</h1>

    <div class="mb-3">
        <strong>Provider:</strong> {{ $invoice->provider->name }}<br>
        <strong>Purchaser:</strong> {{ $invoice->purchaser->name }}<br>
        <strong>Issue Date:</strong> {{ $invoice->issue_date->toDateString() }}<br>
        <strong>Due Date:</strong> {{ $invoice->due_date->toDateString() }}<br>
        <strong>Status:</strong> {{ $invoice->status }}
    </div>

    <h4>Items</h4>
    <table class="table">
        <thead>
            <tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->unit_price }}</td>
                    <td>{{ $item->total }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="float-end">
        <p><strong>Subtotal:</strong> {{ $invoice->subtotal }}</p>
        <p><strong>Tax:</strong> {{ $invoice->tax }}</p>
        <h4><strong>Total:</strong> {{ $invoice->total }} {{ $invoice->currency }}</h4>
    </div>

    <div class="mt-4">
        <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Back</a>
        @can('update', $invoice)
            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-primary">Edit</a>
        @endcan
    </div>
@endsection
