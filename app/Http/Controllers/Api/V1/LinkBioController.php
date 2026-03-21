<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClinicLinkResource;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Models\Clinic;
use App\Models\ClinicLink;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\LinkBioLinkClick;
use App\Models\LinkBioPageView;
use App\Services\ThemeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkBioController extends Controller
{
    public function __construct(private ThemeService $themeService) {}

    /**
     * Dados públicos da página Link Bio por slug (sem autenticação). Usado pelo front em /l/:slug.
     */
    public function publicBySlug(string $slug): JsonResponse
    {
        $clinic = Clinic::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        if (! $clinic) {
            return response()->json(['message' => 'Link Bio não encontrado.'], 404);
        }

        LinkBioPageView::incrementForClinic($clinic->id);

        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $bioLinks = $clinic->bioLinks()->orderBy('sort_order')->get();
        $formLinks = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $clinic->id)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->whereNotNull('public_token')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'public_url' => $base . '/f/' . $t->public_token,
            ]);

        $accentHex = $clinic->public_theme
            ? $this->themeService->getThemeColor($clinic->public_theme)
            : null;

        return response()->json([
            'data' => [
                'clinic' => [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'slug' => $clinic->slug,
                    'logo_url' => $clinic->logo_url,
                    'public_theme' => $clinic->public_theme,
                    'cover_color' => $clinic->cover_color,
                    'cover_mode' => $clinic->cover_mode ?? 'banner',
                    'cover_image_url' => $clinic->cover_image_url,
                    'short_description' => $clinic->short_description,
                    'specialties' => $clinic->specialties,
                    'specialties_list' => $clinic->getSpecialtiesList(),
                    'founded_year' => $clinic->founded_year,
                    'contact_email' => $clinic->contact_email,
                    'phone' => $clinic->phone,
                    'meta_description' => $clinic->meta_description,
                    'maps_url' => $clinic->getMapsUrl(),
                    'accent_hex' => $accentHex,
                    'is_open_now' => $clinic->isOpenNow(),
                    'business_hours_grid' => $clinic->getBusinessHoursGrid(),
                ],
                'links' => ClinicLinkResource::collection($bioLinks),
                'form_links' => $formLinks->values(),
            ],
        ]);
    }

    /**
     * Dados da página Link Bio (mesmo que a view web).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');

        $clinicId = session('current_clinic_id');
        if (! $clinicId) {
            return response()->json(['message' => 'Nenhuma clínica selecionada.'], 422);
        }

        $clinic = Clinic::findOrFail($clinicId);
        $bioLinks = $clinic->bioLinks()->get();
        $base = rtrim(config('app.frontend_url', config('app.url')), '/');
        $publicUrl = $base . '/l/' . $clinic->slug;

        $formLinksPublic = FormTemplate::withoutGlobalScopes()
            ->where('organization_id', $clinic->id)
            ->where('public_enabled', true)
            ->where('is_active', true)
            ->whereNotNull('public_token')
            ->orderBy('name')
            ->get();

        $today = Carbon::today()->toDateString();
        $visitasHoje = (int) LinkBioPageView::where('organization_id', $clinic->id)->where('date', $today)->value('views');
        $totalViews = (int) LinkBioPageView::where('organization_id', $clinic->id)->sum('views');

        $linkIds = $bioLinks->pluck('id')->toArray();
        $totalClicks = empty($linkIds) ? 0 : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)->sum('clicks');
        $totalClicksLast30 = empty($linkIds) ? 0 : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)
            ->where('date', '>=', Carbon::today()->subDays(30)->toDateString())
            ->sum('clicks');

        $taxaClique = $totalViews > 0 ? round($totalClicks / $totalViews * 100, 1) : 0;

        $formTemplatesAll = FormTemplate::withoutGlobalScopes()->where('organization_id', $clinic->id)->get();
        $formulariosTotal = $formTemplatesAll->count();
        $formulariosAtivos = $formLinksPublic->count();
        $formulariosDraft = $formulariosTotal - $formulariosAtivos;

        $bioLinksWithClicks = $clinic->bioLinks()->get();
        $clickCounts = empty($linkIds) ? [] : LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)
            ->selectRaw('clinic_link_id, COALESCE(SUM(clicks), 0) as total')
            ->groupBy('clinic_link_id')
            ->pluck('total', 'clinic_link_id');
        $bioLinksWithClicks->each(function ($link) use ($clickCounts) {
            $link->total_clicks = (int) ($clickCounts[$link->id] ?? 0);
        });

        $mostClickedLink = $bioLinksWithClicks->isEmpty() ? null : $bioLinksWithClicks->sortByDesc('total_clicks')->first();

        $clicksPerDay = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $clicksPerDay[$date] = empty($linkIds) ? 0 : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)->where('date', $date)->sum('clicks');
        }
        $viewsPerDay = [];
        foreach (array_keys($clicksPerDay) as $date) {
            $viewsPerDay[$date] = (int) LinkBioPageView::where('organization_id', $clinic->id)->where('date', $date)->value('views');
        }

        $peakDayLabel = null;
        if ($totalViews > 0) {
            $dayWithMostViews = LinkBioPageView::where('organization_id', $clinic->id)->orderByDesc('views')->first();
            if ($dayWithMostViews) {
                $peakDayLabel = ucfirst(Carbon::parse($dayWithMostViews->date)->locale('pt_BR')->dayName);
            }
        }

        $formTemplatesForTab = $formLinksPublic->map(function ($t) use ($base) {
            $submissions = FormSubmission::withoutGlobalScopes()->where('template_id', $t->id);
            return [
                'id' => $t->id,
                'name' => $t->name,
                'public_url' => $base . '/f/' . $t->public_token,
                'submission_count' => (int) $submissions->count(),
                'last_submission_at' => $submissions->max('submitted_at')?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => [
                'clinic' => new ClinicResource($clinic),
                'bio_links' => ClinicLinkResource::collection($bioLinksWithClicks),
                'form_links_public' => $formTemplatesForTab->values(),
                'public_url' => $publicUrl,
                'available_icons' => ClinicLink::availableIcons(),
                'available_themes' => $this->themeService->getAvailableThemes(),
                'visitas_hoje' => $visitasHoje,
                'total_views' => $totalViews,
                'total_clicks' => $totalClicks,
                'total_clicks_last_30' => $totalClicksLast30,
                'taxa_clique' => $taxaClique,
                'formularios_total' => $formulariosTotal,
                'formularios_ativos' => $formulariosAtivos,
                'formularios_draft' => $formulariosDraft,
                'clicks_per_day' => $clicksPerDay,
                'views_per_day' => $viewsPerDay,
                'most_clicked_link' => $mostClickedLink ? new ClinicLinkResource($mostClickedLink) : null,
                'peak_day_label' => $peakDayLabel,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        $clinic = Clinic::findOrFail($clinicId);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $maxOrder = $clinic->bioLinks()->max('sort_order') ?? -1;
        $link = $clinic->bioLinks()->create([
            'label' => $data['label'],
            'url' => $data['url'],
            'icon' => $data['icon'] ?? 'link',
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json(['data' => new ClinicLinkResource($link)], 201);
    }

    public function update(Request $request, ClinicLink $link): JsonResponse
    {
        $this->authorize('manage-clinic');
        abort_unless((string) $link->clinic_id === (string) session('current_clinic_id'), 403);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'url' => ['required', 'url', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $link->update([
            'label' => $data['label'],
            'url' => $data['url'],
            'icon' => $data['icon'] ?? 'link',
        ]);

        return response()->json(['data' => new ClinicLinkResource($link->fresh())]);
    }

    public function destroy(ClinicLink $link): JsonResponse
    {
        $this->authorize('manage-clinic');
        abort_unless((string) $link->clinic_id === (string) session('current_clinic_id'), 403);

        $link->delete();

        return response()->json(['data' => ['message' => 'Link removido.']], 200);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        $clinicId = session('current_clinic_id');

        foreach ($ids as $order => $id) {
            ClinicLink::where('id', $id)->where('organization_id', $clinicId)->update(['sort_order' => $order]);
        }

        return response()->json(['data' => ['message' => 'Ordem atualizada.']]);
    }

    public function updateAparencia(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        $clinic = Clinic::findOrFail($clinicId);

        $validThemes = array_keys($this->themeService->getAvailableThemes());
        $data = $request->validate([
            'public_theme' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_merge([''], $validThemes))],
            'cover_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'cover_mode' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['banner', 'solid', 'none'])],
            'short_description' => ['nullable', 'string', 'max:200'],
            'specialties' => ['nullable', 'string', 'max:500'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
        ]);

        foreach (['public_theme', 'cover_color', 'cover_mode', 'short_description', 'specialties', 'founded_year', 'contact_email', 'maps_url'] as $key) {
            if (array_key_exists($key, $data) && trim((string) $data[$key]) === '') {
                $data[$key] = null;
            }
        }

        $clinic->update($data);

        return response()->json([
            'data' => new ClinicResource($clinic->fresh()),
        ]);
    }
}
