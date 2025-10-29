<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InvoicePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // allow access to the index; individual view checks are stricter
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        // providers can view invoices they created; purchasers can view invoices assigned to them
        return $user->isProvider() && $invoice->provider_id === $user->id
            || $user->isPurchaser() && $invoice->purchaser_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // only providers create invoices
        return $user->isProvider();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Invoice $invoice): bool
    {
        // only provider who owns the invoice can update it, and only when not paid
        return $user->isProvider() && $invoice->provider_id === $user->id && $invoice->status !== 'paid';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Invoice $invoice): bool
    {
        // allow deletion by provider only when invoice is still a draft
        return $user->isProvider() && $invoice->provider_id === $user->id && $invoice->status === 'draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
