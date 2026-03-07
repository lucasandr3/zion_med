<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\ClinicLink;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\LinkBioLinkClick;
use App\Models\LinkBioPageView;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LinkBioController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $this->authorize('manage-clinic');

        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return redirect()->route('clinica.escolher');
        }

        $clinic    = Clinic::findOrFail($clinicId);
        $bioLinks  = $clinic->bioLinks()->get();
        $publicUrl = route('link-bio.public', $clinic->slug);

        $formLinksPublic = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $clinic->id)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->whereNotNull('public_token')
            ->orderBy('name')
            ->get();

        $today = Carbon::today()->toDateString();

        $visitasHoje = (int) LinkBioPageView::where('organization_id', $clinic->id)
            ->where('date', $today)
            ->value('views');

        $totalViews = (int) LinkBioPageView::where('organization_id', $clinic->id)->sum('views');

        $linkIds = $bioLinks->pluck('id')->toArray();
        $totalClicks = empty($linkIds)
            ? 0
            : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)->sum('clicks');
        $totalClicksLast30 = empty($linkIds)
            ? 0
            : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)
                ->where('date', '>=', Carbon::today()->subDays(30)->toDateString())
                ->sum('clicks');

        $taxaClique = $totalViews > 0 ? round($totalClicks / $totalViews * 100, 1) : 0;

        $formTemplatesAll = FormTemplate::withoutGlobalScopes()->where('organization_id', $clinic->id)->get();
        $formulariosTotal = $formTemplatesAll->count();
        $formulariosAtivos = $formLinksPublic->count();
        $formulariosDraft = $formulariosTotal - $formulariosAtivos;

        $bioLinksWithClicks = $clinic->bioLinks()->get();
        $clickCounts = empty($linkIds)
            ? []
            : LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)
                ->selectRaw('clinic_link_id, COALESCE(SUM(clicks), 0) as total')
                ->groupBy('clinic_link_id')
                ->pluck('total', 'clinic_link_id');
        $bioLinksWithClicks->each(function ($link) use ($clickCounts) {
            $link->total_clicks = (int) ($clickCounts[$link->id] ?? 0);
        });

        $mostClickedLink = $bioLinksWithClicks->isEmpty()
            ? null
            : $bioLinksWithClicks->sortByDesc('total_clicks')->first();

        $clicksPerDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $clicksPerDay[$date] = empty($linkIds)
                ? 0
                : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)
                    ->where('date', $date)
                    ->sum('clicks');
        }

        $viewsPerDay = [];
        foreach (array_keys($clicksPerDay) as $date) {
            $viewsPerDay[$date] = (int) LinkBioPageView::where('organization_id', $clinic->id)
                ->where('date', $date)
                ->value('views');
        }

        $peakDayLabel = null;
        if ($totalViews > 0) {
            $dayWithMostViews = LinkBioPageView::where('organization_id', $clinic->id)
                ->orderByDesc('views')
                ->first();
            if ($dayWithMostViews) {
                $peakDayLabel = Carbon::parse($dayWithMostViews->date)->locale('pt_BR')->dayName;
                $peakDayLabel = ucfirst($peakDayLabel);
            }
        }

        $formTemplatesForTab = $formLinksPublic->map(function ($t) {
            $submissions = FormSubmission::withoutGlobalScopes()->where('template_id', $t->id);
            $t->submission_count = (int) $submissions->count();
            $t->last_submission_at = $submissions->max('submitted_at');
            $t->is_public_active = true;
            return $t;
        });

        return view('link-bio.index', [
            'clinic'                 => $clinic,
            'bioLinks'               => $bioLinksWithClicks,
            'formLinksPublic'        => $formLinksPublic,
            'formTemplatesForTab'    => $formTemplatesForTab,
            'publicUrl'              => $publicUrl,
            'availableIcons'         => ClinicLink::availableIcons(),
            'visitasHoje'            => $visitasHoje,
            'totalViews'              => $totalViews,
            'totalClicks'            => $totalClicks,
            'totalClicksLast30'      => $totalClicksLast30,
            'taxaClique'             => $taxaClique,
            'formulariosTotal'       => $formulariosTotal,
            'formulariosAtivos'      => $formulariosAtivos,
            'formulariosDraft'       => $formulariosDraft,
            'clicksPerDay'           => $clicksPerDay,
            'viewsPerDay'            => $viewsPerDay,
            'mostClickedLink'        => $mostClickedLink,
            'peakDayLabel'            => $peakDayLabel,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage-clinic');

        $clinicId = session('current_clinic_id');
        $clinic   = Clinic::findOrFail($clinicId);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'url'   => ['required', 'url', 'max:500'],
            'icon'  => ['nullable', 'string', 'max:50'],
        ]);

        $maxOrder = $clinic->bioLinks()->max('sort_order') ?? -1;

        $clinic->bioLinks()->create([
            'label'      => $data['label'],
            'url'        => $data['url'],
            'icon'       => $data['icon'] ?? 'link',
            'sort_order' => $maxOrder + 1,
        ]);

        return redirect()->route('link-bio.index')->with('success', 'Link adicionado com sucesso.');
    }

    public function update(Request $request, ClinicLink $link): RedirectResponse
    {
        $this->authorize('manage-clinic');
        abort_unless((string) $link->clinic_id === (string) session('current_clinic_id'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'url'   => ['required', 'url', 'max:500'],
            'icon'  => ['nullable', 'string', 'max:50'],
        ]);

        $link->update([
            'label' => $data['label'],
            'url'   => $data['url'],
            'icon'  => $data['icon'] ?? 'link',
        ]);

        return redirect()->route('link-bio.index')->with('success', 'Link atualizado.');
    }

    public function destroy(ClinicLink $link): RedirectResponse
    {
        $this->authorize('manage-clinic');
        abort_unless((string) $link->clinic_id === (string) session('current_clinic_id'), 403);

        $link->delete();

        return redirect()->route('link-bio.index')->with('success', 'Link removido.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');

        $ids      = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        $clinicId = session('current_clinic_id');

        foreach ($ids as $order => $id) {
            ClinicLink::where('id', $id)->where('organization_id', $clinicId)->update(['sort_order' => $order]);
        }

        return response()->json(['ok' => true]);
    }

    public function public(string $slug): View
    {
        $clinic = Clinic::where('slug', $slug)->firstOrFail();

        LinkBioPageView::incrementForClinic($clinic->id);

        $bioLinks = $clinic->bioLinks()->get();

        $formLinks = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $clinic->id)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->whereNotNull('public_token')
            ->orderBy('name')
            ->get();

        return view('link-bio.public', [
            'clinic'    => $clinic,
            'bioLinks'  => $bioLinks,
            'formLinks' => $formLinks,
        ]);
    }

    /**
     * Redireciona para a URL do link e registra o clique (uma linha por link por dia).
     */
    public function out(string $slug, Request $request): RedirectResponse
    {
        $clinic = Clinic::where('slug', $slug)->firstOrFail();
        $linkId = $request->integer('link');
        $link   = ClinicLink::where('id', $linkId)->where('organization_id', $clinic->id)->firstOrFail();

        LinkBioLinkClick::incrementForLink($link->id);

        return redirect()->away($link->url);
    }
}
