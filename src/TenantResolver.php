<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use SaeedHosan\Tenancy\Contracts\TenantResolver as TenantResolverContract;

/**
 * @template TTenant of Model
 *
 * @implements TenantResolverContract<TTenant>
 */
class TenantResolver implements TenantResolverContract
{
    /**
     * Create a new tenant resolver instance.
     *
     * @param  TenantContext<TTenant>  $tenantContext
     */
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Get the tenant model class name.
     *
     * @return class-string<TTenant>
     */
    public function tenantModel(): string
    {
        if (is_string($model = config('tenancy.tenant_model')) && class_exists($model)) {
            /** @var class-string<TTenant> */
            return $model;
        }

        /** @var class-string<TTenant> */
        return Model::class;
    }

    /**
     * Get the tenant key column name.
     */
    public function tenantKey(): string
    {
        return (string) config('tenancy.tenant_key', 'tenant_id');
    }

    /**
     * Resolve the tenant from the authenticated user.
     *
     * @return TTenant|null
     */
    public function resolveUserTenant(?Authenticatable $user): ?Model
    {
        return null;
    }

    /**
     * Resolve the tenant from the current request.
     *
     * @return TTenant|null
     */
    public function resolveRouteTenant(Request $request): ?Model
    {
        return null;
    }

    /**
     * Resolve the accessible tenant keys for the user.
     *
     * @param  TTenant|null  $userTenant
     * @return array<int, int|string>
     */
    public function resolveAccessibleTenantKeys(?Authenticatable $user, ?Model $userTenant): array
    {
        if ($userTenant instanceof Model) {
            $key = $userTenant->getKey();

            if (is_int($key) || is_string($key)) {
                return [$key];
            }
        }

        return [];
    }

    /**
     * Determine if tenant scoping should be applied.
     */
    public function shouldScope(?Authenticatable $user): bool
    {
        return true;
    }
}
