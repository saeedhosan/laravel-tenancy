<?php

declare(strict_types=1);

namespace SaeedHosan\Tenancy\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;

class TestTenant extends Model
{
    protected $table = 'tenants';

    protected $guarded = [];

    public $timestamps = false;
}

class OtherTenant extends Model
{
    protected $table = 'tenants';

    protected $guarded = [];

    public $timestamps = false;
}

class TenantAwareModel extends Model
{
    use \SaeedHosan\Tenancy\Concerns\HasTenant;

    public $timestamps = false;

    protected $table = 'tenant_aware_models';

    protected $guarded = [];
}

class CustomTenantKeyModel extends Model
{
    use \SaeedHosan\Tenancy\Concerns\HasTenant;

    public $timestamps = false;

    protected $table = 'custom_tenant_models';

    protected $guarded = [];

    public function getTenantKeyName(): string
    {
        return 'tenant_id';
    }
}

class TestUser extends AuthenticatableUser
{
    public $timestamps = false;

    protected $guarded = [];
}

class TestTenantResolver implements \SaeedHosan\Tenancy\Contracts\TenantResolver
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

    public function resolveUserTenant(?\Illuminate\Contracts\Auth\Authenticatable $user): ?Model
    {
        return $this->userTenant;
    }

    public function resolveRouteTenant(\Illuminate\Http\Request $request): ?Model
    {
        return $this->routeTenant;
    }

    public function resolveAccessibleTenantKeys(?\Illuminate\Contracts\Auth\Authenticatable $user, ?Model $userTenant): array
    {
        return $this->accessibleKeys;
    }

    public function shouldScope(?\Illuminate\Contracts\Auth\Authenticatable $user): bool
    {
        return $this->shouldScope;
    }
}
