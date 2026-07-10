<?php

namespace App\Services;

use App\Models\GoAssistantEvent;
use App\Models\User;
use Illuminate\Support\Collection;

class GoAssistantService
{
    /**
     * Registra um evento do Go Assistant para o usuário autenticado.
     *
     * @param  array{
     *   kind: string,
     *   screen_id?: string|null,
     *   intent_id?: string|null,
     *   route?: string|null,
     *   title?: string|null,
     *   query?: string|null,
     *   meta?: array<string, mixed>|null
     * }  $payload
     */
    public function record(User $user, array $payload): GoAssistantEvent
    {
        return GoAssistantEvent::query()->create([
            'user_id' => $user->id,
            'organization_id' => $user->organization_id,
            'kind' => $payload['kind'],
            'screen_id' => $payload['screen_id'] ?? null,
            'intent_id' => $payload['intent_id'] ?? null,
            'route' => $payload['route'] ?? null,
            'title' => $payload['title'] ?? null,
            'query' => isset($payload['query']) ? mb_substr((string) $payload['query'], 0, 500) : null,
            'meta' => $payload['meta'] ?? null,
        ]);
    }

    /**
     * Últimos acessos deduplicados (telas e intents), mais recentes primeiro.
     *
     * @return Collection<int, GoAssistantEvent>
     */
    public function recentHistory(User $user, int $limit = 12): Collection
    {
        $limit = max(1, min($limit, 50));

        $events = GoAssistantEvent::query()
            ->where('user_id', $user->id)
            ->whereIn('kind', [GoAssistantEvent::KIND_SCREEN, GoAssistantEvent::KIND_INTENT])
            ->latest('id')
            ->limit(200)
            ->get();

        $seen = [];
        $unique = collect();

        foreach ($events as $event) {
            $key = $event->kind === GoAssistantEvent::KIND_INTENT
                ? 'intent:'.($event->intent_id ?? '')
                : 'screen:'.($event->screen_id ?? '');

            if ($key === 'intent:' || $key === 'screen:' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique->push($event);

            if ($unique->count() >= $limit) {
                break;
            }
        }

        return $unique->values();
    }

    /**
     * Tutoriais / intents mais abertos pelo usuário.
     *
     * @return list<array{intent_id: string, title: string|null, count: int}>
     */
    public function popularIntents(User $user, int $limit = 8): array
    {
        $limit = max(1, min($limit, 30));

        return GoAssistantEvent::query()
            ->where('user_id', $user->id)
            ->where('kind', GoAssistantEvent::KIND_INTENT)
            ->whereNotNull('intent_id')
            ->selectRaw('intent_id, MAX(title) as title, COUNT(*) as aggregate_count')
            ->groupBy('intent_id')
            ->orderByDesc('aggregate_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'intent_id' => (string) $row->intent_id,
                'title' => $row->title !== null ? (string) $row->title : null,
                'count' => (int) $row->aggregate_count,
            ])
            ->all();
    }

    public function clearHistory(User $user): int
    {
        return GoAssistantEvent::query()
            ->where('user_id', $user->id)
            ->delete();
    }
}
