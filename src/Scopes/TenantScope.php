<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use SaeedHosan\Tenancy\TenantContext;

final class TenantScope implements Scope
{
    /**
     * Apply the tenant scope to a given Eloquent query builder.
     *
     * @param  Builder<Model>  $builder  The Eloquent query builder
     * @param  Model  $model  The model being queried
     */
    public function apply(Builder $builder, Model $model): void
    {

        $context = resolve(TenantContext::class);

        if (! $context->shouldScope()) {
            return;
        }

        $tenantKey = method_exists($model, 'getTenantKeyName')
            ? $model->getTenantKeyName()
            : config('tenancy.tenant_key', 'tenant_id');

        if (! is_string($tenantKey) || $tenantKey === '') {
            $tenantKey = 'tenant_id';
        }

        $tenantKeys = $context->tenantKeys();

        if ($tenantKeys === []) {
            $builder->whereRaw('1 = 0');

            return;
        }

        $builder->whereIn($model->qualifyColumn($tenantKey), $tenantKeys);
    }
}
