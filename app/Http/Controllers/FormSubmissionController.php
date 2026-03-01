<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmissionCommentRequest;
use App\Http\Requests\SubmissionReviewRequest;
use App\Models\Clinic;
use App\Models\FormSubmission;
use App\Services\PdfService;
use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class FormSubmissionController extends Controller
{
    public function __construct(
        private PdfService $pdfService,
        private SubmissionService $submissionService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('view-submissions');
        $query = FormSubmission::with(['template', 'submittedByUser', 'approvedByUser'])
            ->latest();

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

        $protocolos = $query->paginate(20)->withQueryString();
        $templates = \App\Models\FormTemplate::orderBy('name')->get();
        $clinic = Clinic::find(session('current_clinic_id'));

        if ($request->ajax()) {
            return view('protocolos._rows', ['protocolos' => $protocolos]);
        }

        return view('protocolos.index', [
            'protocolos' => $protocolos,
            'templates' => $templates,
            'clinic' => $clinic,
        ]);
    }

    public function show(FormSubmission $submissao)
    {
        $this->authorize('view-submission', $submissao);
        $submissao->load(['template.fields', 'values', 'attachments', 'signatures', 'clinic', 'approvedByUser', 'events.user']);
        return view('protocolos.show', ['protocolo' => $submissao]);
    }

    public function comentario(SubmissionCommentRequest $request, FormSubmission $submissao): \Illuminate\Http\RedirectResponse
    {
        $this->submissionService->addComment($submissao, $request->user()->id, $request->validated('body'));
        return redirect()->route('protocolos.show', $submissao)->with('success', 'Comentário adicionado.');
    }

    public function aprovar(SubmissionReviewRequest $request, FormSubmission $submissao): \Illuminate\Http\RedirectResponse
    {
        $this->submissionService->approve(
            $submissao,
            $request->validated('status'),
            $request->validated('review_comment'),
            $request->user()->id
        );
        $status = $request->status === 'approved' ? 'Aprovado' : 'Reprovado';
        return redirect()->route('protocolos.show', $submissao)->with('success', "Protocolo {$status}.");
    }

    public function pdf(FormSubmission $submissao): Response|StreamedResponse
    {
        $this->authorize('view-submission', $submissao);
        return $this->pdfService->streamSubmissionPdf($submissao);
    }

    public function exportarCsv(Request $request): StreamedResponse
    {
        $this->authorize('view-submissions');
        $query = FormSubmission::with(['template', 'approvedByUser'])
            ->latest();

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

    public function exportarPdfLote(Request $request): Response|StreamedResponse|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view-submissions');
        $query = FormSubmission::with(['template.fields', 'values', 'attachments', 'signatures', 'clinic'])
            ->latest();

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

        $protocolos = $query->limit(50)->get();
        if ($protocolos->isEmpty()) {
            return redirect()->route('protocolos.index')->with('error', 'Nenhum protocolo encontrado para exportar.');
        }

        $zip = new ZipArchive;
        $zipPath = storage_path('app/temp/protocolos-' . now()->format('Y-m-d-His') . '.zip');
        \Illuminate\Support\Facades\File::ensureDirectoryExists(dirname($zipPath));
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->route('protocolos.index')->with('error', 'Não foi possível criar o arquivo ZIP.');
        }

        foreach ($protocolos as $s) {
            $content = $this->pdfService->getSubmissionPdfContent($s);
            $filename = 'protocolo-' . ($s->protocol_number ?? $s->id) . '.pdf';
            $zip->addFromString($filename, $content);
        }
        $zip->close();

        return response()->download($zipPath, basename($zipPath))->deleteFileAfterSend(true);
    }
}
