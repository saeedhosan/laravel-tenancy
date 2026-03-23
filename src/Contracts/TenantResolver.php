<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * @template TTenant of Model
 */
interface TenantResolver
{
    /**
     * @return class-string<TTenant>
     */
    public function tenantModel(): string;

    public function tenantKey(): string;

    /**
     * @return TTenant|null
     */
    public function resolveUserTenant(?Authenticatable $user): ?Model;

    /**
     * @return TTenant|null
     */
    public function resolveRouteTenant(Request $request): ?Model;

    /**
     * @param  TTenant|null  $userTenant
     * @return array<int, int|string>
     */
    public function resolveAccessibleTenantKeys(?Authenticatable $user, ?Model $userTenant): array;

    public function shouldScope(?Authenticatable $user): bool;
}
