<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (! Schema::hasColumn('form_submissions', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('review_comment');
            }
            if (! Schema::hasColumn('form_submissions', 'revoked_by_user_id')) {
                $table->foreignId('revoked_by_user_id')->nullable()->after('revoked_at')
                    ->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('form_submissions', 'revoke_reason')) {
                $table->text('revoke_reason')->nullable()->after('revoked_by_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('form_submissions', 'revoked_by_user_id')) {
                $table->dropConstrainedForeignId('revoked_by_user_id');
            }
            foreach (['revoked_at', 'revoke_reason'] as $col) {
                if (Schema::hasColumn('form_submissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
