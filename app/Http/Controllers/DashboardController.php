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
        $orgId = session('current_clinic_id');
        if (! $orgId) {
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
            ->where('organization_id', $orgId)
            ->where('status', SubmissionStatus::Pending)
            ->whereDate('created_at', today())
            ->count();

        $porStatus = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $ultimos7Dias = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $ultimos30Dias = FormSubmission::withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $ultimosTemplates = \Illuminate\Support\Facades\Cache::remember(
            'org:' . $orgId . ':dashboard:last_templates',
            now()->addMinutes(5),
            function () use ($orgId) {
                return FormTemplate::withoutGlobalScopes()
                    ->where('organization_id', $orgId)
                    ->latest()
                    ->take(5)
                    ->get();
            }
        );

        $linksPublicosCount = \Illuminate\Support\Facades\Cache::remember(
            'org:' . $orgId . ':dashboard:public_links_count',
            now()->addMinutes(5),
            function () use ($orgId) {
                return FormTemplate::withoutGlobalScopes()
                    ->where('organization_id', $orgId)
                    ->whereNotNull('public_token')
                    ->where('public_enabled', true)
                    ->count();
            }
        );

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
