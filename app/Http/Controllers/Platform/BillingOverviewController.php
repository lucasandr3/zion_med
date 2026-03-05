<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;

class BillingOverviewController extends Controller
{
    public function subscriptions(): View
    {
        $subscriptions = Subscription::with(['clinic' => function ($q) {
            $q->select('id', 'name', 'tenant_id', 'plan_key', 'subscription_status', 'billing_status');
        }])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return view('platform.billing.subscriptions', compact('subscriptions'));
    }

    public function payments(): View
    {
        $payments = Payment::with(['clinic' => function ($q) {
            $q->select('id', 'name', 'tenant_id');
        }, 'subscription'])
            ->orderByDesc('due_date')
            ->limit(100)
            ->get();

        return view('platform.billing.payments', compact('payments'));
    }
}

