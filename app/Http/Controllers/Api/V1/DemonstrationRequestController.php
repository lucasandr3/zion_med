<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DemonstrationRequest;
use App\Models\User;
use App\Notifications\NovoLeadPlataforma;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DemonstrationRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'clinic'  => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:200'],
            'phone'   => ['required', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $lead = DemonstrationRequest::create($validated);

        try {
            User::where('is_platform_admin', true)
                ->get()
                ->each(fn (User $u) => $u->notify(new NovoLeadPlataforma($lead)));
        } catch (\Throwable $e) {
            Log::warning('[DemonstrationRequest] Falha ao notificar admins: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Recebemos sua solicitação! Entraremos em contato em breve.',
        ], 201);
    }
}
