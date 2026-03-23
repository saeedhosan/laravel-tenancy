<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The Eloquent model that represents the tenant in this application.
    |
    */
    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | Tenant Key
    |--------------------------------------------------------------------------
    |
    | The foreign key used on tenant-aware models. Override on a per-model basis
    | by implementing getTenantKeyName() on the model.
    |
    */
    'tenant_key' => 'tenant_id',

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolver
    |--------------------------------------------------------------------------
    |
    | The resolver is responsible for determining the current tenant, the
    | accessible tenant keys, and whether scoping should be enabled.
    |
    */
    'resolver' => null,
];
