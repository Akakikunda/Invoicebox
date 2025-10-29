@extends('layouts.app')

@section('content')
    <h1>Create Invoice</h1>

    <form method="POST" action="{{ route('invoices.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Purchaser</label>
            <select name="purchaser_id" class="form-select"> 
                @foreach($purchasers as $p)
                    <option value="{{ $p->id }}">{{ $p->company_name ?? $p->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Invoice Number</label>
            <input type="text" name="invoice_number" class="form-control" required>
        </div>

        <div class="row">
            <div class="col">
                <label class="form-label">Issue Date</label>
                <input type="date" name="issue_date" class="form-control" required>
            </div>
            <div class="col">
                <label class="form-label">Due Date</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>
        </div>

        <hr>
        <h5>Items</h5>
        <div id="items">
            <div class="item row mb-2">
                <div class="col-6"><input name="items[0][description]" class="form-control" placeholder="Description"></div>
                <div class="col-2"><input name="items[0][quantity]" class="form-control" value="1"></div>
                <div class="col-2"><input name="items[0][unit_price]" class="form-control" value="0.00"></div>
                <div class="col-2"><input name="items[0][tax_rate]" class="form-control" value="0"></div>
            </div>
        </div>

        <button type="button" class="btn btn-sm btn-outline-secondary" id="addItem">Add item</button>

        <div class="mt-4">
            <button class="btn btn-primary">Create</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>

    <script>
        let idx = 1;
        document.getElementById('addItem').addEventListener('click', () => {
            const container = document.getElementById('items');
            const div = document.createElement('div');
            div.className = 'item row mb-2';
            div.innerHTML = `
                <div class="col-6"><input name="items[${idx}][description]" class="form-control" placeholder="Description"></div>
                <div class="col-2"><input name="items[${idx}][quantity]" class="form-control" value="1"></div>
                <div class="col-2"><input name="items[${idx}][unit_price]" class="form-control" value="0.00"></div>
                <div class="col-2"><input name="items[${idx}][tax_rate]" class="form-control" value="0"></div>
            `;
            container.appendChild(div);
            idx++;
        });
    </script>
@endsection
