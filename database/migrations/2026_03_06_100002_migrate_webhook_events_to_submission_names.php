<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_TO_NEW = [
        'protocol.submitted' => 'submission.created',
        'protocol.approved' => 'submission.approved',
        'protocol.rejected' => 'submission.rejected',
    ];

    public function up(): void
    {
        $webhooks = DB::table('clinic_webhooks')->get();
        foreach ($webhooks as $webhook) {
            $events = json_decode($webhook->events, true);
            if (! is_array($events)) {
                continue;
            }
            $updated = false;
            $newEvents = [];
            foreach ($events as $event) {
                $newEvents[] = self::OLD_TO_NEW[$event] ?? $event;
                if (isset(self::OLD_TO_NEW[$event])) {
                    $updated = true;
                }
            }
            if ($updated) {
                DB::table('clinic_webhooks')
                    ->where('id', $webhook->id)
                    ->update(['events' => json_encode(array_values(array_unique($newEvents)))]);
            }
        }
    }

    public function down(): void
    {
        $reverse = array_flip(self::OLD_TO_NEW);
        $webhooks = DB::table('clinic_webhooks')->get();
        foreach ($webhooks as $webhook) {
            $events = json_decode($webhook->events, true);
            if (! is_array($events)) {
                continue;
            }
            $newEvents = [];
            foreach ($events as $event) {
                $newEvents[] = $reverse[$event] ?? $event;
            }
            DB::table('clinic_webhooks')
                ->where('id', $webhook->id)
                ->update(['events' => json_encode(array_values(array_unique($newEvents)))]);
        }
    }
};
