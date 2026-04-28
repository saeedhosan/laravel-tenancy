<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use SaeedHosan\Tenancy\TenantContext;

if (! function_exists('currentTenant')) {
    /**
     * Get the current tenant context.
     *
     * @return TenantContext<Model>
     */
    function currentTenant(): TenantContext
    {
        return resolve(TenantContext::class);
    }
}
