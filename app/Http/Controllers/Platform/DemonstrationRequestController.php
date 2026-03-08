<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\DemonstrationRequest;
use Illuminate\Contracts\View\View;

class DemonstrationRequestController extends Controller
{
    public function index(): View
    {
        $requests = DemonstrationRequest::orderByDesc('created_at')->get();

        return view('platform.leads.index', compact('requests'));
    }
}
