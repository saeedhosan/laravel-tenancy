<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use SaeedHosan\Tenancy\Scopes\TenantScope;
use SaeedHosan\Tenancy\TenantContext;

/** @phpstan-ignore-next-line */
trait HasTenant
{
    /**
     * Get the name of the column used for tenant identification.
     */
    public function getTenantKeyName(): string
    {
        return (string) config('tenancy.tenant_key', 'tenant_id');
    }

    /**
     * Boot the HasTenant trait for a model.
     */
    protected static function bootHasTenant(): void
    {

        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {

            $context = resolve(TenantContext::class);

            // Skip auto-assignment if tenant scoping is disabled
            if (! $context->shouldScope()) {
                return;
            }

            $tenantColumn = $model->getTenantKeyName();

            if ($model->getAttribute($tenantColumn)) {
                return;
            }

            $tenantKey = $context->tenantKey();

            if (! is_null($tenantKey)) {
                $model->setAttribute($tenantColumn, $tenantKey);
            }
        });
    }

    /**
     * Scope query to specific tenant(s).
     *
     * @param  int|string|array<int, int|string>|Model|null  $tenant
     */
    protected function scopeForTenant(Builder $query, int|string|array|Model|null $tenant = null): Builder
    {
        $tenantKeys = $this->normalizeTenantKeys($tenant);

        if ($tenantKeys === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($this->qualifyColumn($this->getTenantKeyName()), $tenantKeys);
    }

    /**
     * Scope query to bypass tenant restrictions.
     */
    protected function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Normalize various company input formats to array of company IDs.
     *
     * @param  int|string|array<int, int|string>|Model|null  $tenant
     * @return array<int, int|string>
     */
    private function normalizeTenantKeys(int|string|array|Model|null $tenant): array
    {
        if ($tenant instanceof Model) {
            return [$tenant->getKey()];
        }

        if (is_array($tenant)) {
            return array_values(array_unique(array_map(static function (int|string $key): int|string {
                return is_numeric($key) ? (int) $key : $key;
            }, $tenant)));
        }

        if (is_int($tenant) || is_string($tenant)) {
            return [is_numeric($tenant) ? (int) $tenant : $tenant];
        }

        $context = resolve(TenantContext::class);

        return $context->tenantKeys();
    }
}
