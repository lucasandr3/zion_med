<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PlatformAdminNotifier
{
    public function notify(Notification $notification): void
    {
        try {
            User::query()
                ->where(function ($q): void {
                    $q->where('is_platform_admin', true)
                        ->orWhere('role', 'platform_admin')
                        ->orWhere('role', 'super_admin');
                })
                ->get()
                ->unique('id')
                ->each(fn (User $u) => $u->notify($notification));
        } catch (\Throwable $e) {
            Log::warning('PlatformAdminNotifier failed', ['error' => $e->getMessage()]);
        }
    }
}
