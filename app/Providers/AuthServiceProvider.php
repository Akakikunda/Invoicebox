<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Invoice;
use App\Models\Payment;
use App\Policies\InvoicePolicy;
use App\Policies\PaymentPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // additional gates can be registered here if needed
    }
}
