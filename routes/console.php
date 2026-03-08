<?php

use App\Enums\Role;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AssinaturasPendentesPlataforma;
use App\Notifications\FaturasVencidasPlataforma;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('platform:notify-billing', function () {
    $admins = User::where('role', Role::PlatformAdmin)->get();
    if ($admins->isEmpty()) {
        return;
    }

    $pendingSubs = Subscription::whereIn('status', ['past_due', 'pending', 'PENDING'])->count();
    if ($pendingSubs > 0) {
        Notification::send($admins, new AssinaturasPendentesPlataforma($pendingSubs));
    }

    $overduePayments = Payment::where('due_date', '<', now()->startOfDay())
        ->whereNotIn('status', ['RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH'])
        ->count();
    if ($overduePayments > 0) {
        Notification::send($admins, new FaturasVencidasPlataforma($overduePayments));
    }

    $this->info('Notificações de billing enviadas (assinaturas pendentes: ' . $pendingSubs . ', faturas vencidas: ' . $overduePayments . ').');
})->purpose('Notifica admins da plataforma sobre assinaturas pendentes e faturas vencidas');

Schedule::command('platform:notify-billing')->dailyAt('08:00');
