<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('form_fields')) {
            return;
        }

        Schema::table('form_fields', function (Blueprint $table) {
            $table->text('label')->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('form_fields')) {
            return;
        }

        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('label')->change();
        });
    }
};
