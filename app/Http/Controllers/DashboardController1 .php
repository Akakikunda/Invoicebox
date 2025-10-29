<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        
        if ($user->isProvider()) {
            $invoices = $user->providedInvoices()
                ->with(['purchaser', 'payments'])
                ->latest()
                ->take(5)
                ->get();

            $totalOutstanding = $user->providedInvoices()
                ->where('status', '!=', 'paid')
                ->sum('total');

            $totalPaid = $user->providedInvoices()
                ->where('status', 'paid')
                ->sum('total');

            return view('dashboard.provider', compact('invoices', 'totalOutstanding', 'totalPaid'));
        }

        // Purchaser dashboard
        $invoices = $user->receivedInvoices()
            ->with(['provider', 'payments'])
            ->latest()
            ->take(5)
            ->get();

        $totalUnpaid = $user->receivedInvoices()
            ->where('status', '!=', 'paid')
            ->sum('total');

        $totalPaid = $user->receivedInvoices()
            ->where('status', 'paid')
            ->sum('total');

        return view('dashboard.purchaser', compact('invoices', 'totalUnpaid', 'totalPaid'));
    }
}
