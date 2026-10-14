<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ProductEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductEventController extends Controller
{
    /** Eventos públicos permitidos (landing / comece) sem auth. */
    private const PUBLIC_EVENTS = [
        'signup_started',
    ];

    public function __construct(private ProductEventService $events) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_name' => ['required', 'string', Rule::in(self::PUBLIC_EVENTS)],
            'channel' => ['nullable', 'string', 'max:80'],
            'utm_source' => ['nullable', 'string', 'max:120'],
            'utm_medium' => ['nullable', 'string', 'max:120'],
            'utm_campaign' => ['nullable', 'string', 'max:160'],
            'properties' => ['nullable', 'array'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
        ]);

        $ipHash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $key = $data['idempotency_key']
            ?? ('signup_started:'.$ipHash.':'.now()->format('Y-m-d-H'));

        $this->events->record(
            $data['event_name'],
            null,
            null,
            is_array($data['properties'] ?? null) ? $data['properties'] : [],
            $key,
            [
                'channel' => $data['channel'] ?? 'comece',
                'utm_source' => $data['utm_source'] ?? null,
                'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
            ]
        );

        return response()->json(['data' => ['recorded' => true]]);
    }
}
