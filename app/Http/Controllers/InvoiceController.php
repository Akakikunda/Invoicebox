<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('provider')->only(['create', 'store', 'edit', 'update', 'destroy']);
    }

    public function index()
    {
        $user = auth()->user();
        $invoices = $user->isProvider() 
            ? $user->providedInvoices()->with(['purchaser', 'payments'])->latest()->paginate(10)
            : $user->receivedInvoices()->with(['provider', 'payments'])->latest()->paginate(10);

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $purchasers = User::where('role', 'purchaser')->get();
        return view('invoices.create', compact('purchasers'));
    }

    public function store(StoreInvoiceRequest $request)
    {
        $data = $request->validated();
        $data['provider_id'] = auth()->id();
        $data['status'] = 'draft';

        DB::transaction(function () use ($data) {
            $invoice = Invoice::create([
                'provider_id' => $data['provider_id'],
                'purchaser_id' => $data['purchaser_id'],
                'invoice_number' => $data['invoice_number'],
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'currency' => $data['currency'],
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
            ]);

            $subtotal = 0;
            $tax = 0;

            foreach ($data['items'] as $item) {
                $itemSubtotal = $item['quantity'] * $item['unit_price'];
                $itemTax = $itemSubtotal * ($item['tax_rate'] / 100);

                $invoice->items()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal' => $itemSubtotal,
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $itemTax,
                    'total' => $itemSubtotal + $itemTax,
                ]);

                $subtotal += $itemSubtotal;
                $tax += $itemTax;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ]);
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load(['provider', 'purchaser', 'items', 'payments']);
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        return view('invoices.edit', compact('invoice'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $invoice->update($request->validated());

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
