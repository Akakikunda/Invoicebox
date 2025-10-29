@extends('layouts.app')

@section('content')
    <h1>Edit Invoice {{ $invoice->invoice_number }}</h1>

    <form method="POST" action="{{ route('invoices.update', $invoice) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control">{{ old('notes', $invoice->notes) }}</textarea>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary">Update</button>
            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
