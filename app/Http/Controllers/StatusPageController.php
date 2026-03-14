<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use Illuminate\View\View;

class StatusPageController extends Controller
{
    public function show(): View
    {
        return view('status.show', PlatformSetting::getServiceStatusPayload());
    }
}
