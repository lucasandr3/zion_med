<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TenantDeletionService
{
    /**
     * Remove o tenant, organizações e dados vinculados (cadastros de teste / limpeza admin).
     *
     * @return array{tenant_id:int, tenant_name:string, organizations_count:int, organizations_deleted:list<int>}
     */
    public function delete(Tenant $tenant): array
    {
        return DB::transaction(function () use ($tenant) {
            $tenant->loadMissing('organizations:id,tenant_id,name');
            $organizations = $tenant->organizations;
            $orgIds = $organizations->pluck('id')->map(fn ($id) => (int) $id)->all();

            if ($orgIds !== []) {
                $this->purgeOrganizationSideTables($orgIds);
            }

            // Cascade do banco remove organizations → users, templates, people, submissions, etc.
            $tenantId = (int) $tenant->id;
            $tenantName = (string) $tenant->name;
            $tenant->delete();

            Log::info('Platform admin deleted tenant', [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenantName,
                'organization_ids' => $orgIds,
            ]);

            return [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenantName,
                'organizations_count' => count($orgIds),
                'organizations_deleted' => $orgIds,
            ];
        });
    }

    /**
     * Tabelas sem FK cascade (ou só index) que sujam o banco após delete do tenant.
     *
     * @param  list<int>  $organizationIds
     */
    private function purgeOrganizationSideTables(array $organizationIds): void
    {
        $tables = [
            'product_events',
            'asaas_webhook_events',
            'audit_logs',
            'organization_presences',
            'go_assistant_events',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'organization_id')) {
                continue;
            }
            DB::table($table)->whereIn('organization_id', $organizationIds)->delete();
        }

        if (Schema::hasTable('form_imports') && Schema::hasColumn('form_imports', 'organization_id')) {
            $importIds = DB::table('form_imports')
                ->whereIn('organization_id', $organizationIds)
                ->pluck('id')
                ->all();

            if ($importIds !== [] && Schema::hasTable('form_import_pages')) {
                DB::table('form_import_pages')->whereIn('form_import_id', $importIds)->delete();
            }

            DB::table('form_imports')->whereIn('organization_id', $organizationIds)->delete();
        }
    }
}
