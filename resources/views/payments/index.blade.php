@extends('layouts.app')

@section('content')
    <h1>Payments</h1>
    <table class="table">
        <thead><tr><th>ID</th><th>Invoice</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($payments as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td><a href="{{ route('invoices.show', $p->invoice) }}">{{ $p->invoice->invoice_number }}</a></td>
                    <td>{{ $p->amount }}</td>
                    <td>{{ $p->payment_method }}</td>
                    <td>{{ $p->status }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div>{{ $payments->links() }}</div>
@endsection
