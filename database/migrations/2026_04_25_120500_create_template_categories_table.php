<?php

declare(strict_types=1);

use App\Models\FormTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('name', 120);
            $table->timestamps();

            $table->unique(['organization_id', 'key']);
            $table->index(['organization_id', 'name']);
        });

        $now = now();
        $labelMap = FormTemplate::categoryLabels();

        $rows = DB::table('form_templates')
            ->select('organization_id', 'category')
            ->whereNotNull('organization_id')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->get();

        foreach ($rows as $row) {
            $key = (string) $row->category;
            DB::table('template_categories')->updateOrInsert(
                [
                    'organization_id' => (int) $row->organization_id,
                    'key' => $key,
                ],
                [
                    'name' => $labelMap[$key] ?? $key,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('template_categories');
    }
};
