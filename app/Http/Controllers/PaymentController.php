<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        $payments = $user->isProvider()
            ? Payment::whereHas('invoice', fn($q) => $q->where('provider_id', $user->id))
                ->with(['invoice', 'user'])
                ->latest()
                ->paginate(10)
            : $user->payments()
                ->with(['invoice', 'user'])
                ->latest()
                ->paginate(10);

        return view('payments.index', compact('payments'));
    }

    public function create(Invoice $invoice)
    {
        $this->authorize('create-payment', $invoice);
        return view('payments.create', compact('invoice'));
    }

    public function store(StorePaymentRequest $request)
    {
        $data = $request->validated();
        $invoice = Invoice::findOrFail($data['invoice_id']);
        
        $this->authorize('create-payment', $invoice);

        DB::transaction(function () use ($data, $invoice) {
            $payment = $invoice->payments()->create([
                'user_id' => auth()->id(),
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'],
                'payment_date' => $data['payment_date'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);

            // Check if payment completes the invoice amount
            $totalPaid = $invoice->payments()
                ->where('status', 'completed')
                ->sum('amount') + $data['amount'];

            if ($totalPaid >= $invoice->total) {
                $invoice->update(['status' => 'paid']);
            }
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);
        $payment->load(['invoice', 'user']);
        return view('payments.show', compact('payment'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $this->authorize('update', $payment);
        $payment->update($request->validated());

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Payment status updated successfully.');
    }
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
