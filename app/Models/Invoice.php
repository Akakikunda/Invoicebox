<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'purchaser_id', 
        'invoice_number',
        'issue_date',
        'due_date',
        'total_amount',
        'currency',
        'status',
        'notes'
        
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function purchaser()
    {
        return $this->belongsTo(User::class, 'purchaser_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function getBalanceAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    public function updateStatus()
    {
        $paidAmount = $this->paid_amount;
        
        if ($paidAmount >= $this->total_amount) {
            $this->update(['status' => 'paid']);
        } elseif ($paidAmount > 0) {
            $this->update(['status' => 'partial']);
        } elseif ($this->due_date < now() && $this->status !== 'overdue') {
            $this->update(['status' => 'overdue']);
        }
    }
}

























