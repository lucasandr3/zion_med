<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function config(WebPushService $webPush): JsonResponse
    {
        return response()->json([
            'data' => [
                'enabled' => $webPush->isConfigured(),
                'vapid_public_key' => $webPush->publicKey(),
            ],
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $count = PushSubscription::query()
            ->where('user_id', $request->user()->id)
            ->count();

        return response()->json([
            'data' => [
                'subscribed' => $count > 0,
                'devices' => $count,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:32'],
        ]);

        $sub = PushSubscription::query()->updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['contentEncoding'] ?? 'aesgcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255) ?: null,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'data' => [
                'id' => $sub->id,
                'subscribed' => true,
            ],
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['nullable', 'string', 'max:500'],
        ]);

        $q = PushSubscription::query()->where('user_id', $request->user()->id);
        if (! empty($data['endpoint'])) {
            $q->where('endpoint', $data['endpoint']);
        }
        $q->delete();

        return response()->json([
            'data' => ['subscribed' => false],
        ]);
    }
}
