<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use SaeedHosan\Tenancy\Contracts\TenantResolver;

class TestTenantResolver implements TenantResolver
{
    public function __construct(
        private bool $shouldScope = true,
        private ?Model $userTenant = null,
        private ?Model $routeTenant = null,
        private array $accessibleKeys = [],
    ) {}

    public function tenantModel(): string
    {
        return TestTenant::class;
    }

    public function tenantKey(): string
    {
        return 'tenant_id';
    }

    public function resolveUserTenant(?Authenticatable $user): ?Model
    {
        return $this->userTenant;
    }

    public function resolveRouteTenant(Request $request): ?Model
    {
        return $this->routeTenant;
    }

    public function resolveAccessibleTenantKeys(?Authenticatable $user, ?Model $userTenant): array
    {
        return $this->accessibleKeys;
    }

    public function shouldScope(?Authenticatable $user): bool
    {
        return $this->shouldScope;
    }
}
