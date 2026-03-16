<?php

use App\Enums\Role;
use App\Models\DocumentSend;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AssinaturasPendentesPlataforma;
use App\Notifications\FaturasVencidasPlataforma;
use App\Services\DocumentSendService;
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

$reminderDays = (int) env('DOCUMENT_REMINDER_DAYS', 2);
Artisan::command('documents:send-reminders', function () use ($reminderDays) {
    $service = app(DocumentSendService::class);
    $cutoff = now()->subDays($reminderDays);
    $sends = DocumentSend::notCancelled()
        ->whereNull('form_submission_id')
        ->whereNull('cancelled_at')
        ->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        })
        ->where('sent_at', '<=', $cutoff)
        ->whereNull('reminded_at')
        ->limit(50)
        ->get();
    $sent = 0;
    foreach ($sends as $send) {
        if ($service->sendReminder($send)) {
            $sent++;
        }
    }
    $this->info("Lembretes de documento enviados: {$sent}.");
})->purpose('Envia e-mail de lembrete para documentos pendentes de assinatura (envios antigos)');

Schedule::command('documents:send-reminders')->dailyAt('09:00');
