<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductFeedback;
use App\Models\User;
use App\Notifications\NovoFeedbackPlataforma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['suggestion', 'bug'])],
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'source' => ['nullable', 'string', Rule::in(['account_menu', 'go_assistant'])],
            'page_url' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $feedback = ProductFeedback::create([
            'user_id' => $user->id,
            'organization_id' => $user->currentOrganizationId(),
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'source' => $validated['source'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'user_agent' => substr((string) $request->userAgent(), 0, 500) ?: null,
        ]);

        try {
            User::where('is_platform_admin', true)
                ->get()
                ->each(fn (User $admin) => $admin->notify(new NovoFeedbackPlataforma($feedback)));
        } catch (\Throwable $e) {
            Log::warning('[Feedback] Falha ao notificar admins: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Obrigado! Recebemos seu feedback.',
            'data' => ['id' => $feedback->id],
        ], 201);
    }
}
