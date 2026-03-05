<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('platform.dashboard', [
            'tenantsCount' => Tenant::count(),
            'clinicsCount' => Clinic::count(),
            'usersCount' => User::count(),
        ]);
    }
}

