<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $filter = $request->get('filtro', 'todas');

        $query = $filter === 'nao_lidas'
            ? $user->unreadNotifications()
            : $user->notifications();

        $notifications = $query->latest()->paginate(20)->withQueryString();

        $layout = $user->isPlatformAdmin() ? 'layouts.platform' : 'layouts.app';

        return view('notificacoes.index', [
            'notifications'  => $notifications,
            'unreadCount'    => $user->unreadNotifications()->count(),
            'filter'         => $filter,
            'layout'         => $layout,
        ]);
    }

    public function markAsRead(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('notificacoes.index');

        return redirect($url);
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas as notificações foram marcadas como lidas.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $request->user()->notifications()->findOrFail($id)->delete();

        return back()->with('success', 'Notificação removida.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $request->user()->notifications()->delete();

        return back()->with('success', 'Todas as notificações foram removidas.');
    }
}
