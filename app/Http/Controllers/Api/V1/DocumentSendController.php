<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentSendIndexRequest;
use App\Models\DocumentSend;
use App\Models\FormTemplate;
use App\Models\Person;
use App\Services\DocumentSendService;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentSendController extends Controller
{
    use ResolvesOrganizationContext;

    public function __construct(private DocumentSendService $documentSendService) {}

    /**
     * Lista envios de documentos da clínica (caixas: pendentes, assinados, expirados).
     */
    public function index(DocumentSendIndexRequest $request): JsonResponse
    {
        $orgId = $this->currentOrganizationId($request);
        if (! $orgId) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }

        $validated = $request->validated();

        $query = DocumentSend::with(['formTemplate', 'formSubmission', 'person'])
            ->where('organization_id', $orgId);

        if (! empty($validated['caixa'])) {
            match ($validated['caixa']) {
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
        if (! empty($validated['template_id'])) {
            $query->where('form_template_id', $validated['template_id']);
        }
        if (! empty($validated['channel'])) {
            $query->where('channel', $validated['channel']);
        }

        $paginator = $query->orderByDesc('sent_at')->paginate($request->perPage())->withQueryString();

        return response()->json(
            ApiPagination::wrap($paginator, $paginator->map(fn (DocumentSend $s) => [
                'delivery_status' => $s->sent_at ? 'enviado' : 'nao_enviado',
                'signature_status' => $s->form_submission_id ? 'assinado' : ($s->isCancelled() ? 'cancelado' : ($s->isExpired() ? 'expirado' : 'aguardando_assinatura')),
                'id' => $s->id,
                'form_template_id' => $s->form_template_id,
                'template_name' => $s->formTemplate?->name,
                'person_id' => $s->person_id,
                'recipient_name' => $s->recipient_name,
                'person' => $s->person ? [
                    'id' => $s->person->id,
                    'code' => $s->person->code,
                    'name' => $s->person->name,
                ] : null,
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
            ]))
        );
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
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'recipient_email' => ['nullable', 'email'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $template = FormTemplate::findOrFail($validated['template_id']);
        $this->authorize('update-template', $template);

        $orgId = $this->currentOrganizationId($request);
        if (! $orgId) {
            return response()->json(['message' => 'Nenhuma empresa selecionada.'], 422);
        }

        $person = null;
        if (! empty($validated['person_id'])) {
            $person = Person::query()->where('id', $validated['person_id'])->firstOrFail();
            if ((int) $person->organization_id !== (int) $orgId) {
                abort(404);
            }
        }

        $email = $validated['recipient_email'] ?? $person?->email;
        $phone = $validated['recipient_phone'] ?? $person?->phone;
        $recipientName = $person?->name;

        $expiresAt = isset($validated['expires_at']) ? \Carbon\Carbon::parse($validated['expires_at']) : null;

        if ($validated['channel'] === 'whatsapp') {
            if (! $phone || trim((string) $phone) === '') {
                return response()->json(['message' => 'Informe o telefone ou selecione uma pessoa com telefone cadastrado.'], 422);
            }
            $send = $this->documentSendService->sendByWhatsApp(
                $template,
                trim((string) $phone),
                $expiresAt?->toDateTimeImmutable(),
                $person?->id,
                $recipientName
            );
            if (! $send) {
                return response()->json(['message' => 'WhatsApp não configurado para esta clínica (integração Evolution Go) ou número inválido.'], 503);
            }
            return response()->json([
                'data' => [
                    'message' => 'Link enviado por WhatsApp.',
                    'id' => $send->id,
                    'sent_at' => $send->sent_at->toIso8601String(),
                ],
            ], 201);
        }

        if (! $email || trim((string) $email) === '') {
            return response()->json(['message' => 'Informe o e-mail ou selecione uma pessoa com e-mail cadastrado.'], 422);
        }

        $send = $this->documentSendService->sendByEmail(
            $template,
            trim((string) $email),
            $phone ? trim((string) $phone) : null,
            $expiresAt?->toDateTimeImmutable(),
            $person?->id,
            $recipientName
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
        $orgId = $this->currentOrganizationId($request);
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
        $orgId = $this->currentOrganizationId($request);
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
