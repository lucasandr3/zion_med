<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use Illuminate\Http\Request;

trait ResolvesOrganizationContext
{
    protected function currentOrganizationId(Request $request): ?int
    {
        return $request->user()?->currentOrganizationId();
    }
}
