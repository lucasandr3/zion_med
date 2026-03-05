<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::withCount('clinics')->orderBy('name')->get();

        return view('platform.tenants.index', compact('tenants'));
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load(['clinics' => function ($q) {
            $q->orderBy('name');
        }]);

        $clinics = $tenant->clinics()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('platform.tenants.show', compact('tenant', 'clinics'));
    }
}

