<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public function providedInvoices()
    {
        return $this->hasMany(Invoice::class, 'provider_id');
    }

    public function receivedInvoices()
    {
        return $this->hasMany(Invoice::class, 'purchaser_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function isProvider()
    {
        return $this->role === 'provider';
    }

    public function isPurchaser()
    {
        return $this->role === 'purchaser';
    }
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_name',
        'phone_number',
        'address',
        'preferred_currency',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**'''''''''''''''''''''''''''''''''''''''
     * Get the attributes that should be cast.
     *'''''''''''''''
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
