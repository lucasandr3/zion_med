<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Lista notificações do usuário (paginado).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $filter = $request->get('filtro', 'todas');

        $query = $filter === 'nao_lidas'
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->latest()->paginate(min((int) $request->input('per_page', 20), 50))->withQueryString();

        return response()->json([
            'data' => NotificationResource::collection($notifications->items()),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $user->unreadNotifications()->count(),
            ],
            'links' => [
                'first' => $notifications->url(1),
                'last' => $notifications->url($notifications->lastPage()),
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Marca uma notificação como lida.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'data' => new NotificationResource($notification->fresh()),
        ]);
    }

    /**
     * Marca todas como lidas.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'data' => ['message' => 'Todas as notificações foram marcadas como lidas.'],
        ]);
    }

    /**
     * Remove uma notificação.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->findOrFail($id)->delete();

        return response()->json([
            'data' => ['message' => 'Notificação removida.'],
        ], 200);
    }

    /**
     * Remove todas as notificações.
     */
    public function destroyAll(Request $request): JsonResponse
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'data' => ['message' => 'Todas as notificações foram removidas.'],
        ], 200);
    }
}
