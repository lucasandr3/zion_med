<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProtocolDetailResource;
use App\Http\Resources\Api\V1\ProtocolResource;
use App\Models\FormSubmission;
use App\Services\DossierService;
use App\Services\PdfService;
use App\Services\SubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProtocolController extends Controller
{
    public function __construct(
        private PdfService $pdfService,
        private SubmissionService $submissionService,
        private DossierService $dossierService
    ) {}
    /**
     * Lista protocolos da clínica com filtros e paginação.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view-submissions');

        $query = FormSubmission::with('template')->latest();

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }
        if ($request->filled('busca')) {
            $busca = '%' . $request->busca . '%';
            $query->where(function ($q) use ($busca) {
                $q->where('protocol_number', 'like', $busca)
                    ->orWhere('submitter_name', 'like', $busca)
                    ->orWhere('submitter_email', 'like', $busca);
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $protocols = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => ProtocolResource::collection($protocols->items()),
            'meta' => [
                'current_page' => $protocols->currentPage(),
                'last_page' => $protocols->lastPage(),
                'per_page' => $protocols->perPage(),
                'total' => $protocols->total(),
            ],
            'links' => [
                'first' => $protocols->url(1),
                'last' => $protocols->url($protocols->lastPage()),
                'prev' => $protocols->previousPageUrl(),
                'next' => $protocols->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Exibe um protocolo com valores e template.
     */
    public function show(FormSubmission $protocol): JsonResponse
    {
        $this->authorize('view-submission', $protocol);
        $protocol->load(['template.fields', 'values', 'template']);

        return response()->json([
            'data' => new ProtocolDetailResource($protocol),
        ]);
    }

    /**
     * Timeline de eventos do protocolo (para monitoramento e evidência).
     */
    public function timeline(FormSubmission $protocol): JsonResponse
    {
        $this->authorize('view-submission', $protocol);
        $protocol->load('events.user');
        $events = $protocol->events->map(fn ($e) => [
            'id' => $e->id,
            'type' => $e->type,
            'type_label' => $e->type_label,
            'created_at' => $e->created_at->toIso8601String(),
            'user_name' => $e->user?->name,
            'body' => $e->body,
            'meta' => $e->meta_json,
        ]);
        return response()->json(['data' => $events]);
    }

    /**
     * Exporta protocolos em CSV (stream).
     */
    public function exportarCsv(Request $request): StreamedResponse
    {
        $this->authorize('view-submissions');
        $query = FormSubmission::with(['template', 'approvedByUser'])->latest();

        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('created_at', '<=', $request->data_fim);
        }
        if ($request->filled('busca')) {
            $busca = '%' . $request->busca . '%';
            $query->where(function ($q) use ($busca) {
                $q->where('protocol_number', 'like', $busca)
                    ->orWhere('submitter_name', 'like', $busca)
                    ->orWhere('submitter_email', 'like', $busca);
            });
        }

        $filename = 'protocolos-' . now()->format('Y-m-d-His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Protocolo', 'Template', 'Situação', 'Submetente', 'E-mail', 'Data', 'Revisado em', 'Revisado por'], ';');
            $query->chunk(100, function ($protocolos) use ($handle) {
                foreach ($protocolos as $s) {
                    fputcsv($handle, [
                        $s->id,
                        $s->protocol_number,
                        $s->template->name,
                        $s->status->label(),
                        $s->submitter_name ?? $s->submittedByUser?->name ?? '',
                        $s->submitter_email ?? $s->submittedByUser?->email ?? '',
                        $s->submitted_at?->format('d/m/Y H:i') ?? $s->created_at->format('d/m/Y H:i'),
                        $s->approved_at?->format('d/m/Y H:i') ?? '',
                        $s->approvedByUser?->name ?? '',
                    ], ';');
                }
            });
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Stream do PDF do protocolo.
     */
    public function pdf(FormSubmission $protocol): \Illuminate\Http\Response|StreamedResponse
    {
        $this->authorize('view-submission', $protocol);

        return $this->pdfService->streamSubmissionPdf($protocol);
    }

    /**
     * Exporta dossiê do protocolo (ZIP com PDF + JSON de evidências).
     */
    public function exportarDossie(FormSubmission $protocol): StreamedResponse
    {
        $this->authorize('view-submission', $protocol);

        ['path' => $path, 'filename' => $filename] = $this->dossierService->streamDossierZip($protocol);

        return response()->streamDownload(function () use ($path) {
            $handle = fopen($path, 'r');
            if ($handle) {
                while (! feof($handle)) {
                    echo fread($handle, 8192);
                    flush();
                }
                fclose($handle);
            }
            @unlink($path);
        }, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    }

    /**
     * Aprova ou rejeita o protocolo (revisão).
     */
    public function aprovar(Request $request, FormSubmission $protocol): JsonResponse
    {
        $this->authorize('approve-submission', $protocol);
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
            'review_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->submissionService->approve(
            $protocol,
            $validated['status'],
            $validated['review_comment'] ?? null,
            $request->user()->id
        );

        return response()->json([
            'data' => new ProtocolDetailResource($protocol->fresh(['template', 'values'])),
        ]);
    }

    /**
     * Adiciona comentário ao protocolo.
     */
    public function comentario(Request $request, FormSubmission $protocol): JsonResponse
    {
        $this->authorize('view-submission', $protocol);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $this->submissionService->addComment($protocol, $request->user()->id, $validated['body']);

        return response()->json([
            'data' => ['message' => 'Comentário adicionado.'],
        ], 201);
    }
}
