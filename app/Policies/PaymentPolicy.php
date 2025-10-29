<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // allow index; finer-grained checks on individual records
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        // providers or purchasers related to the invoice or the user who recorded the payment can view
        return $payment->invoice->provider_id === $user->id
            || $payment->invoice->purchaser_id === $user->id
            || $payment->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // purchasers (invoice recipients) or providers that need to record payments can create payments
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payment $payment): bool
    {
        // allow the payment recorder or the invoice provider to update payment (e.g., mark completed)
        return $payment->user_id === $user->id || $payment->invoice->provider_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        // allow deletion by recorder or provider when payment is pending
        return ($payment->user_id === $user->id || $payment->invoice->provider_id === $user->id)
            && $payment->status === 'pending';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }
}
