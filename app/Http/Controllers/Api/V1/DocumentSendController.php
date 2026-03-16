<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DocumentSend;
use App\Models\FormTemplate;
use App\Services\DocumentSendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentSendController extends Controller
{
    public function __construct(private DocumentSendService $documentSendService) {}

    /**
     * Lista envios de documentos da clínica (caixas: pendentes, assinados, expirados).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view-submissions');

        $orgId = session('current_clinic_id') ?? $request->user()?->organization_id ?? $request->user()?->clinic_id;
        if (! $orgId) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }

        $query = DocumentSend::with(['formTemplate', 'formSubmission'])
            ->where('organization_id', $orgId);

        if ($request->filled('caixa')) {
            match ($request->caixa) {
                'pendentes' => $query->notCancelled()
                    ->whereNull('form_submission_id')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    }),
                'assinados' => $query->whereNotNull('form_submission_id'),
                'expirados' => $query->where(function ($q) {
                    $q->whereNull('form_submission_id')->whereNotNull('expires_at')->where('expires_at', '<=', now());
                }),
                'cancelados' => $query->whereNotNull('cancelled_at'),
                default => null,
            };
        }
        if ($request->filled('template_id')) {
            $query->where('form_template_id', $request->template_id);
        }
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $sends = $query->orderByDesc('sent_at')->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $sends->map(fn (DocumentSend $s) => [
                'id' => $s->id,
                'form_template_id' => $s->form_template_id,
                'template_name' => $s->formTemplate?->name,
                'recipient_email' => $s->recipient_email,
                'recipient_phone' => $s->recipient_phone,
                'channel' => $s->channel,
                'sent_at' => $s->sent_at?->toIso8601String(),
                'expires_at' => $s->expires_at?->toIso8601String(),
                'form_submission_id' => $s->form_submission_id,
                'protocol_number' => $s->formSubmission?->protocol_number,
                'status' => $s->isCancelled() ? 'cancelado' : ($s->form_submission_id ? 'assinado' : ($s->isExpired() ? 'expirado' : 'pendente')),
                'cancelled_at' => $s->cancelled_at?->toIso8601String(),
                'reminded_at' => $s->reminded_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $sends->currentPage(),
                'last_page' => $sends->lastPage(),
                'per_page' => $sends->perPage(),
                'total' => $sends->total(),
            ],
            'links' => [
                'first' => $sends->url(1),
                'last' => $sends->url($sends->lastPage()),
                'prev' => $sends->previousPageUrl(),
                'next' => $sends->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Envia o link do documento por e-mail ou WhatsApp (channel no body).
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('manage-templates');

        $validated = $request->validate([
            'template_id' => ['required', 'exists:form_templates,id'],
            'channel' => ['required', 'string', 'in:email,whatsapp'],
            'recipient_email' => ['required_if:channel,email', 'nullable', 'email'],
            'recipient_phone' => ['required_if:channel,whatsapp', 'nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $template = FormTemplate::findOrFail($validated['template_id']);
        $this->authorize('update-template', $template);

        $expiresAt = isset($validated['expires_at']) ? \Carbon\Carbon::parse($validated['expires_at']) : null;

        if (($validated['channel'] ?? 'email') === 'whatsapp') {
            $send = $this->documentSendService->sendByWhatsApp(
                $template,
                $validated['recipient_phone'] ?? '',
                $expiresAt?->toDateTimeImmutable()
            );
            if (! $send) {
                return response()->json(['message' => 'WhatsApp não configurado (N8N_WHATSAPP_WEBHOOK_URL).'], 503);
            }
            return response()->json([
                'data' => [
                    'message' => 'Link enviado por WhatsApp.',
                    'id' => $send->id,
                    'sent_at' => $send->sent_at->toIso8601String(),
                ],
            ], 201);
        }

        $send = $this->documentSendService->sendByEmail(
            $template,
            $validated['recipient_email'] ?? '',
            $validated['recipient_phone'] ?? null,
            $expiresAt?->toDateTimeImmutable()
        );

        return response()->json([
            'data' => [
                'message' => 'Link enviado por e-mail.',
                'id' => $send->id,
                'sent_at' => $send->sent_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Reenvia o link do documento.
     */
    public function reenvio(Request $request, DocumentSend $documentSend): JsonResponse
    {
        $this->authorize('view-submissions');
        $orgId = session('current_clinic_id') ?? $request->user()?->organization_id ?? $request->user()?->clinic_id;
        if ((int) $documentSend->organization_id !== (int) $orgId) {
            abort(404);
        }

        try {
            $send = $this->documentSendService->reenvio($documentSend);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'message' => 'Link reenviado por e-mail.',
                'id' => $send->id,
                'sent_at' => $send->sent_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Cancela um envio (pendente). Não desativa o link do template.
     */
    public function cancel(Request $request, DocumentSend $documentSend): JsonResponse
    {
        $this->authorize('view-submissions');
        $orgId = session('current_clinic_id') ?? $request->user()?->organization_id ?? $request->user()?->clinic_id;
        if ((int) $documentSend->organization_id !== (int) $orgId) {
            abort(404);
        }
        try {
            $this->documentSendService->cancel($documentSend);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json([
            'data' => ['message' => 'Envio cancelado.'],
        ], 200);
    }
}
