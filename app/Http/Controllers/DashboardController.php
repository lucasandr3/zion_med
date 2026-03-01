<?php

namespace App\Http\Controllers;

use App\Enums\SubmissionStatus;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return view('dashboard')->with([
                'semClinica' => true,
                'pendentesHoje' => 0,
                'ultimosTemplates' => collect(),
                'porStatus' => [],
                'ultimos7Dias' => 0,
                'ultimos30Dias' => 0,
                'linksPublicosCount' => 0,
            ]);
        }

        $pendentesHoje = FormSubmission::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->where('status', SubmissionStatus::Pending)
            ->whereDate('created_at', today())
            ->count();

        $porStatus = FormSubmission::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $ultimos7Dias = FormSubmission::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $ultimos30Dias = FormSubmission::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $ultimosTemplates = FormTemplate::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->latest()
            ->take(5)
            ->get();

        $linksPublicosCount = FormTemplate::withoutGlobalScopes()
            ->where('clinic_id', $clinicId)
            ->whereNotNull('public_token')
            ->where('public_enabled', true)
            ->count();

        return view('dashboard', [
            'semClinica' => false,
            'pendentesHoje' => $pendentesHoje,
            'ultimosTemplates' => $ultimosTemplates,
            'porStatus' => $porStatus,
            'ultimos7Dias' => $ultimos7Dias,
            'ultimos30Dias' => $ultimos30Dias,
            'linksPublicosCount' => $linksPublicosCount,
        ]);
    }
}
