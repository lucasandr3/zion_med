<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('clinics', 'organizations');

        $tablesWithClinicId = [
            'users',
            'form_templates',
            'form_submissions',
            'audit_logs',
            'protocol_sequences',
            'subscriptions',
            'payments',
            'link_bio_page_views',
            'clinic_links',
            'clinic_webhooks',
        ];

        foreach ($tablesWithClinicId as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (! Schema::hasColumn($table, 'clinic_id')) {
                continue;
            }
            if ($table === 'users') {
                Schema::table('users', function (Blueprint $t) {
                    $t->dropUnique(['clinic_id', 'email']);
                });
            }
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['clinic_id']);
                $t->renameColumn('clinic_id', 'organization_id');
            });
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('organization_id')->references('id')->on('organizations')->cascadeOnDelete();
            });
            if ($table === 'users') {
                Schema::table('users', function (Blueprint $t) {
                    $t->unique(['organization_id', 'email']);
                });
            }
        }
    }

    public function down(): void
    {
        $tablesWithOrganizationId = [
            'users',
            'form_templates',
            'form_submissions',
            'audit_logs',
            'protocol_sequences',
            'subscriptions',
            'payments',
            'link_bio_page_views',
            'clinic_links',
            'clinic_webhooks',
        ];

        foreach ($tablesWithOrganizationId as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'organization_id')) {
                continue;
            }
            if ($table === 'users') {
                Schema::table('users', function (Blueprint $t) {
                    $t->dropUnique(['organization_id', 'email']);
                });
            }
            Schema::table($table, function (Blueprint $t) {
                $t->dropForeign(['organization_id']);
                $t->renameColumn('organization_id', 'clinic_id');
            });
            Schema::table($table, function (Blueprint $t) {
                $t->foreign('clinic_id')->references('id')->on('organizations')->cascadeOnDelete();
            });
            if ($table === 'users') {
                Schema::table('users', function (Blueprint $t) {
                    $t->unique(['clinic_id', 'email']);
                });
            }
        }

        Schema::rename('organizations', 'clinics');

        foreach (array_merge($tablesWithOrganizationId, ['clinic_webhooks']) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'clinic_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropForeign(['clinic_id']);
                    $t->foreign('clinic_id')->references('id')->on('clinics')->cascadeOnDelete();
                });
            }
        }
    }
};
