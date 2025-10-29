<?php

namespace App\Models;

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
        'subtotal',
        'tax',
        'total',
        'currency',
        'status',
        'notes',
    ];

    protected $dates = [
        'issue_date',
        'due_date',
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
}