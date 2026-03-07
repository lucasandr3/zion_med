<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class ClinicScope implements Scope
{
    /**
     * Coluna de tenant: prioriza a que existe no fillable do modelo (evita cache de schema).
     * Tabelas migradas (clinics → organizations) usam organization_id.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = session('current_clinic_id'); // valor = organization id (tenant atual)
        if ($tenantId === null) {
            return;
        }
        $table = $model->getTable();
        $fillable = $model->getFillable();
        $key = in_array('organization_id', $fillable, true)
            ? 'organization_id'
            : (in_array('clinic_id', $fillable, true) ? 'clinic_id' : null);
        if ($key === null) {
            if (Schema::hasColumn($table, 'organization_id')) {
                $key = 'organization_id';
            } elseif (Schema::hasColumn($table, 'clinic_id')) {
                $key = 'clinic_id';
            }
        }
        if ($key !== null && Schema::hasColumn($table, $key)) {
            $builder->where($table . '.' . $key, $tenantId);
        }
    }
}
