<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_tool_message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key', 40); // confirmacao | documentos | retorno | orcamento | custom
            $table->string('label', 120);
            $table->text('body');
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'key']);
        });

        Schema::create('mobile_tool_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('items'); // [{id,label,done}]
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('mobile_tool_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('client_name', 160);
            $table->string('label', 160);
            $table->unsignedSmallInteger('total_sessions');
            $table->unsignedSmallInteger('done_sessions')->default(0);
            $table->string('status', 20)->default('active'); // active | completed | archived
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('mobile_tool_stock_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 160);
            $table->string('quantity_label', 80)->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('status', 32)->default('ok'); // ok | low | expiring | expired
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('mobile_tool_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('client_name', 160);
            $table->string('detail', 255);
            $table->string('status', 20)->default('pending'); // pending | contacted | archived
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        Schema::create('mobile_tool_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('client_name', 160);
            $table->string('procedure', 160);
            $table->unsignedSmallInteger('sessions');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->text('message');
            $table->timestamps();

            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_tool_quotes');
        Schema::dropIfExists('mobile_tool_followups');
        Schema::dropIfExists('mobile_tool_stock_items');
        Schema::dropIfExists('mobile_tool_packages');
        Schema::dropIfExists('mobile_tool_checklists');
        Schema::dropIfExists('mobile_tool_message_templates');
    }
};
