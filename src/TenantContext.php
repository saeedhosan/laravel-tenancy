<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Manages the current tenant context for multi-tenancy support.
 * Handles company identification, scoping, and access control.
 *
 * @template TTenant of Model
 */
class TenantContext
{
    /**
     * The currently active tenant.
     *
     * @var TTenant|null
     */
    private ?Model $tenant = null;

    /**
     * List of accessible tenant keys for the current user/context.
     *
     * @var array<int, int|string>
     */
    private array $tenantKeys = [];

    /**
     * Whether tenant scoping is currently enabled.
     */
    private bool $scoped = false;

    /**
     * Set the current active tenant.
     *
     * @param  TTenant|null  $tenant
     */
    public function setTenant(?Model $tenant): void
    {
        if ($tenant instanceof Model) {
            $this->assertTenantModel($tenant);
        }

        $this->tenant = $tenant;
    }

    /**
     * Get the currently active tenant.
     *
     * @return TTenant|null
     */
    public function tenant(): ?Model
    {
        return $this->tenant;
    }

    /**
     * Get the current tenant
     *
     * @return TTenant|null
     */
    public function current(): ?Model
    {
        return $this->tenant();
    }

    /**
     * Get the key of the currently active tenant.
     */
    public function tenantKey(): int|string|null
    {
        if (! $this->tenant instanceof Model) {
            return null;
        }

        return $this->normalizeTenantKey($this->tenant);
    }

    /**
     * Set the list of accessible tenant keys.
     *
     * @param  array<int, int|string|Model|null>  $tenantKeys
     */
    public function setTenantKeys(array $tenantKeys): void
    {
        $normalizedKeys = array_map(
            fn (int|string|Model|null $key): int|string|null => $this->normalizeTenantKey($key),
            $tenantKeys
        );

        $normalizedKeys = array_filter($normalizedKeys, static fn (int|string|null $key): bool => ! is_null($key));

        $this->tenantKeys = array_values(array_unique($normalizedKeys, SORT_STRING));
    }

    /**
     * Get the list of accessible tenant keys.
     *
     * @return array<int, int|string>
     */
    public function tenantKeys(): array
    {
        // Return explicitly set tenant keys if available
        if ($this->tenantKeys !== []) {
            return $this->tenantKeys;
        }

        if (! $this->tenant instanceof Model) {
            return [];
        }

        $tenantKey = $this->normalizeTenantKey($this->tenant);

        return $tenantKey === null ? [] : [$tenantKey];
    }

    /**
     * Enable or disable tenant scoping globally.
     */
    public function setScoped(bool $scoped): void
    {
        $this->scoped = $scoped;
    }

    /**
     * Check if tenant scoping is currently enabled.
     */
    public function shouldScope(): bool
    {
        return $this->scoped;
    }

    /**
     * Check if a specific tenant key is accessible in the current context.
     */
    public function isTenantAccessible(int|string $tenantKey): bool
    {

        if (! $this->shouldScope()) {
            return true;
        }

        return in_array($tenantKey, $this->tenantKeys(), true);
    }

    /**
     * Normalize a tenant key input.
     */
    private function normalizeTenantKey(int|string|Model|null $key): int|string|null
    {
        if ($key instanceof Model) {

            $this->assertTenantModel($key);

            $modelKey = $key->getKey();

            if ($modelKey === null) {
                return null;
            }

            if (is_int($modelKey) || is_string($modelKey)) {
                return is_numeric($modelKey) ? (int) $modelKey : $modelKey;
            }

            if (is_numeric($modelKey)) {
                return (int) $modelKey;
            }

            if (is_scalar($modelKey)) {
                return (string) $modelKey;
            }

            return null;
        }

        if (is_int($key)) {
            return $key;
        }

        if (is_string($key)) {

            $trimmed = trim($key);

            if ($trimmed === '') {
                return null;
            }

            return is_numeric($trimmed) ? (int) $trimmed : $trimmed;
        }

        return null;
    }

    /**
     * Ensure the tenant matches the configured tenant model.
     */
    private function assertTenantModel(Model $tenant): void
    {
        $model = $this->tenantModel();

        if (! $tenant instanceof $model) {
            throw new InvalidArgumentException(sprintf('Tenant must be an instance of %s.', $model));
        }
    }

    /**
     * @return class-string<Model>
     */
    private function tenantModel(): string
    {
        /** @var class-string|null $model */
        $model = config('tenancy.tenant_model');

        if (is_string($model) && class_exists($model) && is_subclass_of($model, Model::class)) {
            return $model;
        }

        return Model::class;
    }
}
