<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClinicLinkResource;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Models\Clinic;
use App\Models\ClinicLink;
use App\Models\FormSubmission;
use App\Models\FormTemplate;
use App\Models\LinkBioCtaClick;
use App\Models\LinkBioLinkClick;
use App\Models\LinkBioPageView;
use App\Services\ThemeService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class LinkBioController extends Controller
{
    /**
     * Layouts do Link Bio (mesmos IDs do front Angular).
     */
    private const LINK_BIO_LAYOUT_MODELS = [1, 2, 3, 4, 5, 6, 7, 8];

    /** CTAs da página pública (botões que não são links cadastrados na aba Links). */
    private const LINK_BIO_CTA_CHANNELS = ['whatsapp', 'maps', 'email', 'phone', 'instagram', 'team_whatsapp'];

    public function __construct(private ThemeService $themeService) {}

    /**
     * Redireciona para a URL do link público e registra o clique (estatísticas).
     */
    public function publicRedirectLink(string $slug, int $linkId): Response
    {
        $clinic = Clinic::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        if (! $clinic) {
            abort(404, 'Link Bio não encontrado.');
        }

        $clinicLink = ClinicLink::query()
            ->withoutGlobalScopes()
            ->whereKey($linkId)
            ->where('organization_id', $clinic->id)
            ->first();

        if (! $clinicLink) {
            abort(404, 'Link não encontrado.');
        }

        LinkBioLinkClick::incrementForLink((int) $clinicLink->id);

        return redirect()->away($clinicLink->url);
    }

    /**
     * Registra clique em CTA (WhatsApp, maps, e-mail, etc.) e redireciona para o destino real.
     *
     * @param  string  $channel  whatsapp|maps|email|phone|instagram|team_whatsapp
     */
    public function publicRedirectCta(Request $request, string $slug, string $channel): Response
    {
        if (! in_array($channel, self::LINK_BIO_CTA_CHANNELS, true)) {
            abort(404);
        }

        $clinic = Clinic::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        if (! $clinic) {
            abort(404, 'Link Bio não encontrado.');
        }

        $ref = (string) $request->query('ref', '');
        if ($channel === 'team_whatsapp' && $ref === '') {
            abort(404, 'Referência da equipe ausente.');
        }
        if ($channel !== 'team_whatsapp') {
            $ref = '';
        }

        $target = $this->resolveLinkBioCtaTargetUrl($clinic, $channel, $ref !== '' ? $ref : null);
        if ($target === null || $target === '') {
            abort(404, 'Link indisponível.');
        }

        LinkBioCtaClick::incrementFor((int) $clinic->id, $channel, $ref);

        return redirect()->away($target);
    }

    /**
     * Dados públicos da página Link Bio por slug (sem autenticação). Usado pelo front em /l/:slug.
     */
    public function publicBySlug(Request $request, string $slug): JsonResponse
    {
        $clinic = Clinic::withoutGlobalScopes()
            ->where('slug', $slug)
            ->first();

        if (! $clinic) {
            return response()->json(['message' => 'Link Bio não encontrado.'], 404);
        }

        if ($request->query('preview') !== '1') {
            LinkBioPageView::incrementForClinic($clinic->id);
        }

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

        $accentHex = $this->themeService->getPublicAccentHex(
            $clinic->public_theme,
            $clinic->accent_hex,
        );

        return response()->json([
            'data' => [
                'clinic' => [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'slug' => $clinic->slug,
                    'logo_url' => $clinic->logo_url,
                    'company_logo_url' => null,
                    'professional_photo_url' => $clinic->professional_photo_url,
                    'public_theme' => $clinic->public_theme,
                    'cover_color' => $clinic->cover_color,
                    'cover_mode' => $clinic->cover_mode ?? 'banner',
                    'link_bio_model' => (int) ($clinic->link_bio_model ?? 1),
                    'link_bio_extra' => $clinic->link_bio_extra,
                    'address' => $clinic->address,
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
        $cut30 = Carbon::today()->subDays(30)->toDateString();

        $bioLinkClicksTotal = empty($linkIds) ? 0 : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)->sum('clicks');
        $ctaClicksTotal = (int) LinkBioCtaClick::where('organization_id', $clinic->id)->sum('clicks');
        $totalClicks = $bioLinkClicksTotal + $ctaClicksTotal;

        $bioLinkClicksLast30 = empty($linkIds) ? 0 : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)
            ->where('date', '>=', $cut30)
            ->sum('clicks');
        $ctaClicksLast30 = (int) LinkBioCtaClick::where('organization_id', $clinic->id)
            ->where('date', '>=', $cut30)
            ->sum('clicks');
        $totalClicksLast30 = $bioLinkClicksLast30 + $ctaClicksLast30;

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
            $bioDay = empty($linkIds) ? 0 : (int) LinkBioLinkClick::whereIn('clinic_link_id', $linkIds)->where('date', $date)->sum('clicks');
            $ctaDay = (int) LinkBioCtaClick::where('organization_id', $clinic->id)->where('date', $date)->sum('clicks');
            $clicksPerDay[$date] = $bioDay + $ctaDay;
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
            $lastSubmissionAt = $submissions->max('submitted_at');
            $lastSubmissionAtIso = $lastSubmissionAt ? Carbon::parse($lastSubmissionAt)->toIso8601String() : null;

            return [
                'id' => $t->id,
                'name' => $t->name,
                'public_url' => $base . '/f/' . $t->public_token,
                'submission_count' => (int) $submissions->count(),
                'last_submission_at' => $lastSubmissionAtIso,
            ];
        });

        $clickBreakdown = [];
        foreach ($bioLinksWithClicks as $link) {
            $totalBio = (int) ($clickCounts[$link->id] ?? 0);
            $last30Bio = (int) LinkBioLinkClick::where('clinic_link_id', $link->id)->where('date', '>=', $cut30)->sum('clicks');
            if ($totalBio === 0 && $last30Bio === 0) {
                continue;
            }
            $clickBreakdown[] = [
                'kind' => 'bio_link',
                'id' => $link->id,
                'label' => $link->label,
                'total_clicks' => $totalBio,
                'total_last_30' => $last30Bio,
            ];
        }

        $ctaGrouped = LinkBioCtaClick::where('organization_id', $clinic->id)
            ->selectRaw('channel, ref, SUM(clicks) as total')
            ->groupBy('channel', 'ref')
            ->get();

        foreach ($ctaGrouped as $row) {
            $channel = (string) $row->channel;
            $ref = (string) $row->ref;
            $totalCtaRow = (int) $row->total;
            $last30Cta = (int) LinkBioCtaClick::where('organization_id', $clinic->id)
                ->where('channel', $channel)
                ->where('ref', $ref)
                ->where('date', '>=', $cut30)
                ->sum('clicks');
            if ($totalCtaRow === 0 && $last30Cta === 0) {
                continue;
            }
            $clickBreakdown[] = [
                'kind' => 'cta',
                'channel' => $channel,
                'ref' => $ref === '' ? null : $ref,
                'label' => $this->linkBioCtaLabel($clinic, $channel, $ref),
                'total_clicks' => $totalCtaRow,
                'total_last_30' => $last30Cta,
            ];
        }

        usort($clickBreakdown, function (array $a, array $b): int {
            $cmp = $b['total_last_30'] <=> $a['total_last_30'];
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string) $a['label'], (string) $b['label']);
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
                'click_breakdown' => $clickBreakdown,
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

        $validThemes = $this->themeService->publicThemeKeysForValidation();
        $data = $request->validate([
            'public_theme' => ['nullable', 'string', \Illuminate\Validation\Rule::in(array_merge([''], $validThemes))],
            'accent_hex' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'cover_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'cover_mode' => ['nullable', 'string', \Illuminate\Validation\Rule::in(['banner', 'solid', 'none'])],
            'link_bio_model' => ['nullable', 'integer', \Illuminate\Validation\Rule::in(self::LINK_BIO_LAYOUT_MODELS)],
            'link_bio_extra' => ['nullable', 'array'],
            'short_description' => ['nullable', 'string', 'max:200'],
            'specialties' => ['nullable', 'string', 'max:500'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:' . date('Y')],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'maps_url' => ['nullable', 'url', 'max:500'],
        ]);

        foreach (['public_theme', 'accent_hex', 'cover_color', 'cover_mode', 'short_description', 'specialties', 'founded_year', 'contact_email', 'maps_url'] as $key) {
            if (array_key_exists($key, $data) && trim((string) $data[$key]) === '') {
                $data[$key] = null;
            }
        }

        if (array_key_exists('public_theme', $data) && $data['public_theme'] !== null) {
            $data['public_theme'] = $this->themeService->normalizePublicThemeValue($data['public_theme']);
        }

        // accent_hex só faz sentido quando o tema é `custom`; para qualquer outro tema (incluindo
        // `onyx-black` ou presets), limpamos para que a cor do link público seja derivada do preset.
        if (array_key_exists('public_theme', $data)) {
            if ($data['public_theme'] !== 'custom') {
                $data['accent_hex'] = null;
            } elseif (! array_key_exists('accent_hex', $data) || $data['accent_hex'] === null) {
                // custom sem cor → mantém o que já estava salvo (não sobrescreve com null).
                unset($data['accent_hex']);
            } else {
                $data['accent_hex'] = strtolower($data['accent_hex']);
            }
        }

        $clinic->update($data);

        return response()->json([
            'data' => new ClinicResource($clinic->fresh()),
        ]);
    }

    /**
     * Upload da foto do profissional para o Link Bio (avatar grande nos layouts temáticos).
     */
    public function uploadProfessionalPhoto(Request $request): JsonResponse
    {
        $this->authorize('manage-clinic');
        $clinicId = session('current_clinic_id');
        $clinic = Clinic::findOrFail($clinicId);

        $request->validate(
            [
                'professional_photo' => ['required', 'image', 'max:2048'],
            ],
            [
                'professional_photo.required' => 'Selecione uma imagem para enviar.',
                'professional_photo.image' => 'O arquivo precisa ser uma imagem (JPG, PNG, WebP, etc.).',
                'professional_photo.max' => 'A foto é muito grande. O tamanho máximo permitido é 2 MB (2048 KB). Reduza a imagem e tente novamente.',
            ]
        );

        $file = $request->file('professional_photo');
        if ($clinic->professional_photo_path) {
            Storage::disk('minio_assets')->delete($clinic->professional_photo_path);
            Storage::disk('public')->delete($clinic->professional_photo_path);
        }

        $path = $file->store('organizations/' . $clinic->id . '/link-bio', 'minio_assets');
        $clinic->update(['professional_photo_path' => $path]);

        return response()->json([
            'data' => new ClinicResource($clinic->fresh()),
        ]);
    }

    private function linkBioCtaLabel(Clinic $clinic, string $channel, string $ref): string
    {
        if ($channel === 'team_whatsapp') {
            $idx = (int) $ref;
            $extra = $clinic->link_bio_extra;
            $team = is_array($extra) ? ($extra['team'] ?? null) : null;
            $name = is_array($team) && isset($team[$idx]['name']) ? trim((string) $team[$idx]['name']) : '';

            return $name !== '' ? 'WhatsApp — ' . $name : 'WhatsApp (equipe)';
        }

        return match ($channel) {
            'whatsapp' => 'WhatsApp',
            'maps' => 'Como chegar / Maps',
            'email' => 'E-mail',
            'phone' => 'Ligação (telefone)',
            'instagram' => 'Instagram',
            default => $channel,
        };
    }

    private function resolveLinkBioCtaTargetUrl(Clinic $clinic, string $channel, ?string $ref): ?string
    {
        return match ($channel) {
            'whatsapp' => $this->buildWhatsappUrlFromPhone($clinic->phone),
            'phone' => $this->buildTelUrlFromPhone($clinic->phone),
            'maps' => $clinic->getMapsUrl(),
            'email' => $clinic->contact_email ? 'mailto:' . trim((string) $clinic->contact_email) : null,
            'instagram' => $this->resolveInstagramUrlForCta($clinic),
            'team_whatsapp' => $this->resolveTeamWhatsappUrl($clinic, $ref),
            default => null,
        };
    }

    private function resolveInstagramUrlForCta(Clinic $clinic): ?string
    {
        $extra = $clinic->link_bio_extra;
        if (is_array($extra) && ! empty($extra['instagram_url'])) {
            $u = trim((string) $extra['instagram_url']);
            if ($u !== '') {
                return $u;
            }
        }

        $link = $clinic->bioLinks()->get()->first(function ($l) {
            return str_contains(strtolower((string) $l->url), 'instagram.com');
        });

        return $link?->url;
    }

    private function resolveTeamWhatsappUrl(Clinic $clinic, ?string $ref): ?string
    {
        if ($ref === null || $ref === '') {
            return null;
        }
        $idx = (int) $ref;
        $extra = $clinic->link_bio_extra;
        $team = is_array($extra) ? ($extra['team'] ?? null) : null;
        if (! is_array($team) || ! isset($team[$idx]['whatsapp'])) {
            return null;
        }
        $raw = preg_replace('/\D/', '', (string) $team[$idx]['whatsapp']);
        if ($raw === '') {
            return null;
        }

        return 'https://wa.me/' . $raw;
    }

    private function buildWhatsappUrlFromPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $raw = preg_replace('/\D/', '', $phone);
        if ($raw === '') {
            return null;
        }
        $wa = strlen($raw) >= 10 && strlen($raw) <= 11 ? '55' . $raw : $raw;

        return 'https://wa.me/' . $wa;
    }

    private function buildTelUrlFromPhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $raw = trim($phone);
        if (str_starts_with($raw, '+')) {
            return 'tel:' . $raw;
        }
        $d = preg_replace('/\D/', '', $raw);
        if ($d === '') {
            return null;
        }
        $intl = strlen($d) <= 11 && ! str_starts_with($d, '55') ? '55' . $d : $d;

        return 'tel:+' . $intl;
    }
}
