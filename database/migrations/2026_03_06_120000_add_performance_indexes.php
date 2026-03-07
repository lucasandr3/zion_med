<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('form_templates')) {
            Schema::table('form_templates', function (Blueprint $table) {
                if (! $this->hasIndex($table, 'form_templates_organization_id_index')) {
                    $table->index('organization_id');
                }
                if (! $this->hasIndex($table, 'form_templates_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }

        if (Schema::hasTable('form_submissions')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                if (! $this->hasIndex($table, 'form_submissions_organization_id_index')) {
                    $table->index('organization_id');
                }
                if (! $this->hasIndex($table, 'form_submissions_template_id_index')) {
                    $table->index('template_id');
                }
                if (! $this->hasIndex($table, 'form_submissions_status_index')) {
                    $table->index('status');
                }
                if (! $this->hasIndex($table, 'form_submissions_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }

        if (Schema::hasTable('submission_values')) {
            Schema::table('submission_values', function (Blueprint $table) {
                if (! $this->hasIndex($table, 'submission_values_submission_id_index')) {
                    $table->index('submission_id');
                }
            });
        }

        if (Schema::hasTable('submission_attachments')) {
            Schema::table('submission_attachments', function (Blueprint $table) {
                if (! $this->hasIndex($table, 'submission_attachments_submission_id_index')) {
                    $table->index('submission_id');
                }
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                if (! $this->hasIndex($table, 'audit_logs_organization_id_index')) {
                    $table->index('organization_id');
                }
                if (! $this->hasIndex($table, 'audit_logs_created_at_index')) {
                    $table->index('created_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('form_templates')) {
            Schema::table('form_templates', function (Blueprint $table) {
                $table->dropIndex(['organization_id']);
                $table->dropIndex(['created_at']);
            });
        }

        if (Schema::hasTable('form_submissions')) {
            Schema::table('form_submissions', function (Blueprint $table) {
                $table->dropIndex(['organization_id']);
                $table->dropIndex(['template_id']);
                $table->dropIndex(['status']);
                $table->dropIndex(['created_at']);
            });
        }

        if (Schema::hasTable('submission_values')) {
            Schema::table('submission_values', function (Blueprint $table) {
                $table->dropIndex(['submission_id']);
            });
        }

        if (Schema::hasTable('submission_attachments')) {
            Schema::table('submission_attachments', function (Blueprint $table) {
                $table->dropIndex(['submission_id']);
            });
        }

        if (Schema::hasTable('audit_logs')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex(['organization_id']);
                $table->dropIndex(['created_at']);
            });
        }
    }

    private function hasIndex(Blueprint $table, string $indexName): bool
    {
        // Schema manager não está disponível aqui; confiar no dropIndex no down().
        return false;
    }
};

