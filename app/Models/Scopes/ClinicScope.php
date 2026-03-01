<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Schema;

class ClinicScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $clinicId = session('current_clinic_id');
        if ($clinicId !== null && Schema::hasColumn($model->getTable(), 'clinic_id')) {
            $builder->where($model->getTable() . '.clinic_id', $clinicId);
        }
    }
}
