<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobile_tool_followups', function (Blueprint $table) {
            if (! Schema::hasColumn('mobile_tool_followups', 'phone')) {
                $table->string('phone', 32)->nullable()->after('client_name');
            }
            if (! Schema::hasColumn('mobile_tool_followups', 'due_on')) {
                $table->date('due_on')->nullable()->after('detail');
            }
        });

        Schema::table('mobile_tool_quotes', function (Blueprint $table) {
            if (! Schema::hasColumn('mobile_tool_quotes', 'person_id')) {
                $table->unsignedBigInteger('person_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('mobile_tool_quotes', 'phone')) {
                $table->string('phone', 32)->nullable()->after('client_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mobile_tool_followups', function (Blueprint $table) {
            if (Schema::hasColumn('mobile_tool_followups', 'phone')) {
                $table->dropColumn('phone');
            }
            if (Schema::hasColumn('mobile_tool_followups', 'due_on')) {
                $table->dropColumn('due_on');
            }
        });

        Schema::table('mobile_tool_quotes', function (Blueprint $table) {
            if (Schema::hasColumn('mobile_tool_quotes', 'person_id')) {
                $table->dropColumn('person_id');
            }
            if (Schema::hasColumn('mobile_tool_quotes', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
