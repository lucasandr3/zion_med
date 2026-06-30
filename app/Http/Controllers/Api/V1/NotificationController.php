<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationIndexRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Support\ApiPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Lista notificações do usuário (paginado).
     */
    public function index(NotificationIndexRequest $request): JsonResponse
    {
        $user = $request->user();
        $filter = $request->filter();

        $query = $filter === 'nao_lidas'
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->latest()->paginate($request->perPage())->withQueryString();

        return response()->json(array_merge(
            ApiPagination::wrap($notifications, NotificationResource::collection($notifications->items())),
            [
                'meta' => array_merge(
                    ApiPagination::meta($notifications),
                    ['unread_count' => $user->unreadNotifications()->count()]
                ),
            ]
        ));
    }

    /**
     * Marca uma notificação como lida.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $this->authorize('view-notifications');

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
        $this->authorize('view-notifications');

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
        $this->authorize('view-notifications');

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
        $this->authorize('view-notifications');

        $request->user()->notifications()->delete();

        return response()->json([
            'data' => ['message' => 'Todas as notificações foram removidas.'],
        ], 200);
    }
}
