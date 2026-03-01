<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $submissions = DB::table('form_submissions')->select('id', 'created_at')->get();
        $now = now();
        foreach ($submissions as $s) {
            DB::table('submission_events')->insert([
                'form_submission_id' => $s->id,
                'type' => 'created',
                'user_id' => null,
                'body' => null,
                'created_at' => $s->created_at ?? $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('submission_events')->where('type', 'created')->delete();
    }
};
