<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
   



public function index()
{
    $user = Auth::user();
    
    // Debug - check what user type we have
    \Log::info("User type: " . $user->user_type . " | Email: " . $user->email);
    
    if ($user->user_type === 'provider') {
        return $this->providerDashboard($user);
    } else {
        return $this->purchaserDashboard($user);
    }
}












    private function providerDashboard($user)
    {
        $stats = [
            'total_invoices' => Invoice::where('provider_id', $user->id)->count(),
            'outstanding_amount' => Invoice::where('provider_id', $user->id)
                ->whereIn('status', ['sent', 'partial'])
                ->sum('total_amount'),
            'overdue_invoices' => Invoice::where('provider_id', $user->id)
                ->where('status', 'overdue')
                ->count(),
            'paid_this_month' => Payment::whereHas('invoice', function($query) use ($user) {
                $query->where('provider_id', $user->id);
            })->whereMonth('payment_date', now()->month)
            ->sum('amount')
        ];

        $recentInvoices = Invoice::where('provider_id', $user->id)
            ->with('purchaser')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.provider', compact('stats', 'recentInvoices'));
    }

    private function purchaserDashboard($user)
    {
        $stats = [
            'total_invoices' => Invoice::where('purchaser_id', $user->id)->count(),
            'amount_owed' => Invoice::where('purchaser_id', $user->id)
                ->whereIn('status', ['sent', 'partial', 'overdue'])
                ->sum('total_amount'),
            'due_this_week' => Invoice::where('purchaser_id', $user->id)
                ->where('due_date', '<=', now()->addWeek())
                ->whereIn('status', ['sent', 'partial'])
                ->count(),
            'overdue_invoices' => Invoice::where('purchaser_id', $user->id)
                ->where('status', 'overdue')
                ->count()
        ];

        $pendingInvoices = Invoice::where('purchaser_id', $user->id)
            ->whereIn('status', ['sent', 'partial', 'overdue'])
            ->with('provider')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.purchaser', compact('stats', 'pendingInvoices'));
    }
}