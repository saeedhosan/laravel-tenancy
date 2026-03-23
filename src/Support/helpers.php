<?php

declare(strict_types=1);

use SaeedHosan\Tenancy\TenantContext;

if (! function_exists('currentTenant')) {
    /**
     * Get the current tenant context.
     *
     * @return TenantContext<\Illuminate\Database\Eloquent\Model>
     */
    function currentTenant(): TenantContext
    {
        return resolve(TenantContext::class);
    }
}
