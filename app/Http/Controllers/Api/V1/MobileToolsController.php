<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesOrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\MobileToolChecklist;
use App\Models\MobileToolFollowup;
use App\Models\MobileToolMessageTemplate;
use App\Models\MobileToolPackage;
use App\Models\MobileToolQuote;
use App\Models\MobileToolStockItem;
use App\Services\MobileTools\MobileToolPricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class MobileToolsController extends Controller
{
    use ResolvesOrganizationContext;

    public function __construct(
        private readonly MobileToolPricingService $pricing,
    ) {}

    public function catalog(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);

        return response()->json([
            'data' => [
                'organization_id' => $orgId,
                'tools' => [
                    ['key' => 'preco', 'name' => 'Preço e lucro', 'category' => 'Financeiro', 'ready' => true],
                    ['key' => 'orcamento', 'name' => 'Orçamento rápido', 'category' => 'Financeiro', 'ready' => true],
                    ['key' => 'mensagens', 'name' => 'Mensagens prontas', 'category' => 'Relacionamento', 'ready' => true],
                    ['key' => 'checklist', 'name' => 'Checklist da sala', 'category' => 'Organização', 'ready' => true],
                    ['key' => 'pacotes', 'name' => 'Pacotes e sessões', 'category' => 'Organização', 'ready' => true],
                    ['key' => 'estoque', 'name' => 'Estoque e validade', 'category' => 'Organização', 'ready' => true],
                    ['key' => 'parcelamento', 'name' => 'Taxas de cartão', 'category' => 'Financeiro', 'ready' => true],
                    ['key' => 'retornos', 'name' => 'Retornos', 'category' => 'Relacionamento', 'ready' => true],
                ],
            ],
        ]);
    }

    public function calculatePrice(Request $request): JsonResponse
    {
        $this->requireOrg($request);
        $v = $request->validate([
            'materials' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'minutes' => ['required', 'numeric', 'min:0', 'max:24*60'],
            'hourly_rate' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'margin_percent' => ['required', 'numeric', 'min:0', 'max:99.99'],
        ]);

        try {
            $result = $this->pricing->suggestPrice(
                (float) $v['materials'],
                (float) $v['minutes'],
                (float) $v['hourly_rate'],
                (float) $v['fee_percent'],
                (float) $v['margin_percent'],
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $result]);
    }

    public function quotesIndex(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $rows = MobileToolQuote::query()
            ->where('organization_id', $orgId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function previewQuote(Request $request): JsonResponse
    {
        $this->requireOrg($request);
        $v = $request->validate([
            'client_name' => ['nullable', 'string', 'max:160'],
            'procedure' => ['nullable', 'string', 'max:160'],
            'sessions' => ['required', 'integer', 'min:1', 'max:500'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'save' => ['nullable', 'boolean'],
            'person_id' => ['nullable', 'integer'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);

        try {
            $quote = $this->pricing->buildQuote(
                (string) ($v['client_name'] ?? ''),
                (string) ($v['procedure'] ?? ''),
                (int) $v['sessions'],
                (float) $v['unit_price'],
                (float) ($v['discount'] ?? 0),
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $saved = null;
        if (! empty($v['save'])) {
            $orgId = (int) $this->currentOrganizationId($request);
            $saved = MobileToolQuote::create([
                'organization_id' => $orgId,
                'user_id' => $request->user()?->id,
                'person_id' => $v['person_id'] ?? null,
                'client_name' => trim((string) ($v['client_name'] ?? '')) ?: 'Cliente',
                'phone' => $v['phone'] ?? null,
                'procedure' => trim((string) ($v['procedure'] ?? '')) ?: 'Procedimento',
                'sessions' => (int) $v['sessions'],
                'unit_price' => (float) $v['unit_price'],
                'discount' => (float) ($v['discount'] ?? 0),
                'total' => $quote['total'],
                'message' => $quote['message'],
            ]);
        }

        return response()->json([
            'data' => array_merge($quote, [
                'id' => $saved?->id,
            ]),
        ]);
    }

    public function calculateCard(Request $request): JsonResponse
    {
        $this->requireOrg($request);
        $v = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'fee_percent' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        try {
            $result = $this->pricing->cardNet((float) $v['amount'], (float) $v['fee_percent']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => array_merge($result, [
                'installments' => (int) ($v['installments'] ?? 1),
            ]),
        ]);
    }

    public function messagesIndex(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $this->ensureDefaultMessages($orgId, (int) $request->user()->id);

        $rows = MobileToolMessageTemplate::query()
            ->where('organization_id', $orgId)
            ->where(function ($q) use ($request) {
                $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function messagesUpdate(Request $request, MobileToolMessageTemplate $template): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $this->assertOrg($template->organization_id, $orgId);

        $v = $request->validate([
            'label' => ['sometimes', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $template->update($v);

        return response()->json(['data' => $template->fresh()]);
    }

    public function checklistShow(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $userId = (int) $request->user()->id;

        $row = MobileToolChecklist::query()
            ->where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->first();

        if (! $row) {
            $row = MobileToolChecklist::create([
                'organization_id' => $orgId,
                'user_id' => $userId,
                'items' => $this->defaultChecklistItems(),
            ]);
        }

        return response()->json(['data' => $row]);
    }

    public function checklistUpdate(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $userId = (int) $request->user()->id;

        $v = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:40'],
            'items.*.id' => ['required', 'string', 'max:40'],
            'items.*.label' => ['required', 'string', 'max:200'],
            'items.*.done' => ['required', 'boolean'],
        ]);

        $row = MobileToolChecklist::query()->updateOrCreate(
            ['organization_id' => $orgId, 'user_id' => $userId],
            ['items' => $v['items']],
        );

        return response()->json(['data' => $row]);
    }

    public function checklistReset(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $userId = (int) $request->user()->id;

        $row = MobileToolChecklist::query()->updateOrCreate(
            ['organization_id' => $orgId, 'user_id' => $userId],
            ['items' => $this->defaultChecklistItems()],
        );

        return response()->json(['data' => $row]);
    }

    public function packagesIndex(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $rows = MobileToolPackage::query()
            ->where('organization_id', $orgId)
            ->where('status', '!=', 'archived')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function packagesStore(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $v = $request->validate([
            'client_name' => ['required', 'string', 'max:160'],
            'label' => ['required', 'string', 'max:160'],
            'total_sessions' => ['required', 'integer', 'min:1', 'max:500'],
            'done_sessions' => ['nullable', 'integer', 'min:0', 'max:500'],
            'person_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $done = (int) ($v['done_sessions'] ?? 0);
        $total = (int) $v['total_sessions'];
        if ($done > $total) {
            return response()->json(['message' => 'Sessões feitas não podem exceder o total.'], 422);
        }

        $row = MobileToolPackage::create([
            'organization_id' => $orgId,
            'user_id' => $request->user()?->id,
            'person_id' => $v['person_id'] ?? null,
            'client_name' => $v['client_name'],
            'label' => $v['label'],
            'total_sessions' => $total,
            'done_sessions' => $done,
            'status' => $done >= $total ? 'completed' : 'active',
            'notes' => $v['notes'] ?? null,
        ]);

        return response()->json(['data' => $row], 201);
    }

    public function packagesRegisterSession(Request $request, MobileToolPackage $package): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $this->assertOrg($package->organization_id, $orgId);

        if ($package->done_sessions >= $package->total_sessions) {
            return response()->json(['message' => 'Pacote já concluído.'], 422);
        }

        $package->done_sessions = $package->done_sessions + 1;
        if ($package->done_sessions >= $package->total_sessions) {
            $package->status = 'completed';
        }
        $package->save();

        return response()->json(['data' => $package->fresh()]);
    }

    public function packagesDestroy(Request $request, MobileToolPackage $package): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $this->assertOrg($package->organization_id, $orgId);
        $package->update(['status' => 'archived']);

        return response()->json(['data' => ['message' => 'Pacote arquivado.']]);
    }

    public function stockIndex(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $rows = MobileToolStockItem::query()
            ->where('organization_id', $orgId)
            ->orderBy('name')
            ->limit(200)
            ->get()
            ->map(function (MobileToolStockItem $item) {
                $item->status = $this->resolveStockStatus($item);

                return $item;
            });

        return response()->json(['data' => $rows]);
    }

    public function stockStore(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $v = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'quantity_label' => ['nullable', 'string', 'max:80'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'expires_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $row = MobileToolStockItem::create([
            'organization_id' => $orgId,
            'user_id' => $request->user()?->id,
            'name' => $v['name'],
            'quantity_label' => $v['quantity_label'] ?? null,
            'quantity' => $v['quantity'] ?? null,
            'expires_on' => $v['expires_on'] ?? null,
            'notes' => $v['notes'] ?? null,
            'status' => 'ok',
        ]);
        $row->status = $this->resolveStockStatus($row);
        $row->save();

        return response()->json(['data' => $row], 201);
    }

    public function stockUpdate(Request $request, MobileToolStockItem $item): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $this->assertOrg($item->organization_id, $orgId);

        $v = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'quantity_label' => ['nullable', 'string', 'max:80'],
            'quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'expires_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['ok', 'low', 'expiring', 'expired'])],
        ]);

        $item->fill($v);
        if (! array_key_exists('status', $v) || $v['status'] === null) {
            $item->status = $this->resolveStockStatus($item);
        }
        $item->save();

        return response()->json(['data' => $item->fresh()]);
    }

    public function stockDestroy(Request $request, MobileToolStockItem $item): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $this->assertOrg($item->organization_id, $orgId);
        $item->delete();

        return response()->json(['data' => ['message' => 'Item removido.']]);
    }

    public function followupsIndex(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $rows = MobileToolFollowup::query()
            ->where('organization_id', $orgId)
            ->where('status', '!=', 'archived')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw('due_on is null')
            ->orderBy('due_on')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function followupsStore(Request $request): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $v = $request->validate([
            'client_name' => ['required', 'string', 'max:160'],
            'detail' => ['required', 'string', 'max:255'],
            'person_id' => ['nullable', 'integer'],
            'phone' => ['nullable', 'string', 'max:32'],
            'due_on' => ['nullable', 'date'],
        ]);

        $row = MobileToolFollowup::create([
            'organization_id' => $orgId,
            'user_id' => $request->user()?->id,
            'person_id' => $v['person_id'] ?? null,
            'client_name' => $v['client_name'],
            'phone' => $v['phone'] ?? null,
            'detail' => $v['detail'],
            'due_on' => $v['due_on'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $row], 201);
    }

    public function followupsToggle(Request $request, MobileToolFollowup $followup): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $this->assertOrg($followup->organization_id, $orgId);

        if ($followup->status === 'pending') {
            $followup->status = 'contacted';
            $followup->contacted_at = now();
        } else {
            $followup->status = 'pending';
            $followup->contacted_at = null;
        }
        $followup->save();

        return response()->json(['data' => $followup->fresh()]);
    }

    public function followupsDestroy(Request $request, MobileToolFollowup $followup): JsonResponse
    {
        $orgId = $this->requireOrg($request);
        $this->assertOrg($followup->organization_id, $orgId);
        $followup->update(['status' => 'archived']);

        return response()->json(['data' => ['message' => 'Retorno arquivado.']]);
    }

    private function requireOrg(Request $request): int
    {
        $orgId = $this->currentOrganizationId($request);
        if (! $orgId) {
            abort(422, 'Organização não selecionada.');
        }

        return (int) $orgId;
    }

    private function assertOrg(int|string|null $rowOrgId, int $orgId): void
    {
        if ((int) $rowOrgId !== $orgId) {
            abort(404);
        }
    }

    private function ensureDefaultMessages(int $orgId, int $userId): void
    {
        $exists = MobileToolMessageTemplate::query()
            ->where('organization_id', $orgId)
            ->exists();
        if ($exists) {
            return;
        }

        $defaults = [
            ['key' => 'confirmacao', 'label' => 'Confirmação de atendimento', 'body' => 'Olá, {nome}! Passando para confirmar nosso atendimento de {procedimento}. Se precisar ajustar o horário, me avise.', 'sort_order' => 1],
            ['key' => 'documentos', 'label' => 'Pendência de documentação', 'body' => 'Olá, {nome}! Antes do atendimento de {procedimento}, precisamos concluir a documentação. Vou te encaminhar a ficha pelo Gestgo.', 'sort_order' => 2],
            ['key' => 'retorno', 'label' => 'Acompanhamento/retorno', 'body' => 'Olá, {nome}! Tudo bem? Estou entrando em contato para acompanhar como você está após o procedimento de {procedimento}.', 'sort_order' => 3],
            ['key' => 'orcamento', 'label' => 'Follow-up de orçamento', 'body' => 'Olá, {nome}! Passando para saber se ficou alguma dúvida sobre a proposta de {procedimento}. Estou à disposição.', 'sort_order' => 4],
        ];

        foreach ($defaults as $row) {
            MobileToolMessageTemplate::create([
                'organization_id' => $orgId,
                'user_id' => $userId,
                'key' => $row['key'],
                'label' => $row['label'],
                'body' => $row['body'],
                'is_default' => true,
                'sort_order' => $row['sort_order'],
            ]);
        }
    }

    /** @return list<array{id: string, label: string, done: bool}> */
    private function defaultChecklistItems(): array
    {
        $labels = [
            'Conferir agendamentos e duração dos procedimentos',
            'Separar materiais e EPIs adequados ao serviço',
            'Conferir limpeza e organização do ambiente',
            'Confirmar ficha e consentimento necessários',
            'Verificar disponibilidade e validade de insumos',
            'Registrar orientações e próximas tarefas',
        ];

        $out = [];
        foreach ($labels as $i => $label) {
            $out[] = [
                'id' => 'c'.($i + 1),
                'label' => $label,
                'done' => false,
            ];
        }

        return $out;
    }

    private function resolveStockStatus(MobileToolStockItem $item): string
    {
        if ($item->expires_on) {
            $today = now()->startOfDay();
            $expires = $item->expires_on->copy()->startOfDay();
            if ($expires->lt($today)) {
                return 'expired';
            }
            if ($expires->lte($today->copy()->addDays(30))) {
                return 'expiring';
            }
        }
        if ($item->quantity !== null && $item->quantity <= 2) {
            return 'low';
        }

        return $item->status ?: 'ok';
    }
}
