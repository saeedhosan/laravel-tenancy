<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use SaeedHosan\Tenancy\Contracts\TenantResolver;
use SaeedHosan\Tenancy\Http\Middleware\TenantMiddleware;
use SaeedHosan\Tenancy\TenantContext;
use SaeedHosan\Tenancy\Tests\Models\CustomTenantKeyModel;
use SaeedHosan\Tenancy\Tests\Models\TenantAwareModel;
use SaeedHosan\Tenancy\Tests\Models\TestTenant;
use SaeedHosan\Tenancy\Tests\Models\TestTenantResolver;
use SaeedHosan\Tenancy\Tests\Models\TestUser;

uses(RefreshDatabase::class);

beforeEach(function (): void {

    config()->set('tenancy.tenant_model', TestTenant::class);
    config()->set('tenancy.tenant_key', 'tenant_id');

    Schema::dropIfExists('custom_tenant_models');
    Schema::dropIfExists('tenant_aware_models');
    Schema::dropIfExists('tenants');

    Schema::create('tenants', function (Blueprint $table): void {
        $table->increments('id');
        $table->string('name')->nullable();
    });

    Schema::create('tenant_aware_models', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('tenant_id')->nullable();
        $table->string('name')->nullable();
    });

    Schema::create('custom_tenant_models', function (Blueprint $table): void {
        $table->increments('id');
        $table->unsignedInteger('tenant_id')->nullable();
        $table->string('name')->nullable();
    });
});

it('auto assigns tenant id on creation when scoped', function (): void {
    $tenant = TestTenant::create(['name' => 'Tenant A']);
    $context = app(TenantContext::class);
    $context->setScoped(true);
    $context->setTenant($tenant);

    $model = TenantAwareModel::create(['name' => 'Record A']);

    expect($model->tenant_id)->toBe($tenant->getKey());
    expect(TenantAwareModel::query()->count())->toBe(1);
});

it('does not override explicit tenant id on creation', function (): void {
    $tenant = TestTenant::create(['name' => 'Tenant A']);
    $context = app(TenantContext::class);
    $context->setScoped(true);
    $context->setTenant($tenant);

    $model = TenantAwareModel::create(['name' => 'Record A', 'tenant_id' => 999]);

    expect($model->tenant_id)->toBe(999);
});

it('does not auto assign when scoping is disabled', function (): void {
    $tenant = TestTenant::create(['name' => 'Tenant A']);
    $context = app(TenantContext::class);
    $context->setScoped(false);
    $context->setTenant($tenant);

    $model = TenantAwareModel::create(['name' => 'Record A']);

    expect($model->tenant_id)->toBeNull();
});

it('scopes queries to context companies when no tenant is provided', function (): void {
    $context = app(TenantContext::class);
    $context->setScoped(true);
    $context->setTenantKeys([1, 2]);

    TenantAwareModel::create(['name' => 'Record A', 'tenant_id' => 1]);
    TenantAwareModel::create(['name' => 'Record B', 'tenant_id' => 2]);
    TenantAwareModel::create(['name' => 'Record C', 'tenant_id' => 3]);

    $companies = TenantAwareModel::query()
        ->forTenant()
        ->pluck('tenant_id')
        ->sort()
        ->values()
        ->all();

    expect($companies)->toBe([1, 2]);
});

it('returns no results when scoped to an empty tenant list', function (): void {
    TenantAwareModel::create(['name' => 'Record A', 'tenant_id' => 1]);

    $results = TenantAwareModel::query()->forTenant([])->get();

    expect($results)->toHaveCount(0);
});

it('uses a custom tenant key name when provided', function (): void {
    $context = app(TenantContext::class);
    $context->setScoped(true);
    $context->setTenantKeys([11]);

    CustomTenantKeyModel::create(['name' => 'Record A', 'tenant_id' => 11]);
    CustomTenantKeyModel::create(['name' => 'Record B', 'tenant_id' => 12]);

    $records = CustomTenantKeyModel::query()->pluck('tenant_id')->all();

    expect($records)->toBe([11]);
});

it('applies global tenant scope and can be bypassed', function (): void {
    $context = app(TenantContext::class);
    $context->setScoped(true);
    $context->setTenantKeys([1]);

    TenantAwareModel::create(['name' => 'Record A', 'tenant_id' => 1]);
    TenantAwareModel::create(['name' => 'Record B', 'tenant_id' => 2]);

    expect(TenantAwareModel::query()->count())->toBe(1);
    expect(TenantAwareModel::query()->withoutTenant()->count())->toBe(2);
});

it('returns default tenant key name from config', function (): void {
    $model = new TenantAwareModel;

    expect($model->getTenantKeyName())->toBe('tenant_id');
});

describe('TenantMiddleware', function (): void {
    it('allows guest requests without tenant scoping', function (): void {
        $context = app(TenantContext::class);
        $context->setScoped(true);
        $context->setTenantKeys([1]);
        $context->setTenant(TestTenant::create(['name' => 'Tenant A']));

        $resolver = new TestTenantResolver(
            shouldScope: false,
            userTenant: null,
            routeTenant: null,
            accessibleKeys: [1]
        );

        app()->instance(TenantResolver::class, $resolver);

        $request = Request::create('/test', 'GET');
        $middleware = new TenantMiddleware;

        $response = $middleware->handle($request, function () {
            return response('ok');
        });

        expect($response->getStatusCode())->toBe(200);
        expect($context->shouldScope())->toBeFalse();
        expect($context->tenant())->toBeNull();
        expect($context->tenantKeys())->toBe([]);
    });

    it('allows administrator users without tenant scoping', function (): void {
        $context = app(TenantContext::class);
        $context->setScoped(true);
        $context->setTenantKeys([1]);

        $resolver = new TestTenantResolver(
            shouldScope: false,
            userTenant: null,
            routeTenant: null,
            accessibleKeys: [1]
        );

        app()->instance(TenantResolver::class, $resolver);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => new TestUser);

        $middleware = new TenantMiddleware;

        $response = $middleware->handle($request, function () {
            return response('ok');
        });

        expect($response->getStatusCode())->toBe(200);
        expect($context->shouldScope())->toBeFalse();
        expect($context->tenant())->toBeNull();
        expect($context->tenantKeys())->toBe([]);
    });

    it('sets tenant context from route when authorized', function (): void {
        $userTenant = TestTenant::create(['name' => 'Tenant A']);
        $routeTenant = TestTenant::create(['name' => 'Tenant B']);

        $resolver = new TestTenantResolver(
            shouldScope: true,
            userTenant: $userTenant,
            routeTenant: $routeTenant,
            accessibleKeys: [$userTenant->getKey(), $routeTenant->getKey()]
        );

        app()->instance(TenantResolver::class, $resolver);

        $request = Request::create('/test', 'GET');
        $middleware = new TenantMiddleware;

        $middleware->handle($request, function () {
            return response('ok');
        });

        $context = app(TenantContext::class);

        expect($context->shouldScope())->toBeTrue();
        expect($context->tenant()?->getKey())->toBe($routeTenant->getKey());
        expect($context->tenantKeys())->toBe([$userTenant->getKey(), $routeTenant->getKey()]);
    });

    it('throws authorization exception when route tenant is not in accessible keys', function (): void {
        $userTenant = TestTenant::create(['name' => 'Tenant A']);
        $routeTenant = TestTenant::create(['name' => 'Tenant B']);

        $resolver = new TestTenantResolver(
            shouldScope: true,
            userTenant: $userTenant,
            routeTenant: $routeTenant,
            accessibleKeys: [$userTenant->getKey()]
        );

        app()->instance(TenantResolver::class, $resolver);

        $request = Request::create('/test', 'GET');
        $middleware = new TenantMiddleware;

        $middleware->handle($request, function () {
            return response('ok');
        });
    })->throws(AuthorizationException::class);

    it('throws authorization exception when route tenant key is string and not in accessible keys', function (): void {
        $userTenant = TestTenant::create(['name' => 'Tenant A']);
        $routeTenant = TestTenant::create(['name' => 'Tenant B']);

        $resolver = new TestTenantResolver(
            shouldScope: true,
            userTenant: $userTenant,
            routeTenant: $routeTenant,
            accessibleKeys: [$userTenant->getKey()]
        );

        app()->instance(TenantResolver::class, $resolver);

        $request = Request::create('/test', 'GET');
        $request->attributes->set('tenant', (string) $routeTenant->getKey());
        $middleware = new TenantMiddleware;

        $middleware->handle($request, function () {
            return response('ok');
        });
    })->throws(AuthorizationException::class);
});

describe('TenantScope', function (): void {
    it('applies global scope and filters by tenant keys', function (): void {
        $tenant = TestTenant::create(['name' => 'Tenant A']);
        $context = app(TenantContext::class);
        $context->setScoped(true);
        $context->setTenantKeys([$tenant->getKey()]);

        TenantAwareModel::create(['name' => 'Record A', 'tenant_id' => $tenant->getKey()]);
        TenantAwareModel::create(['name' => 'Record B', 'tenant_id' => 999]);

        expect(TenantAwareModel::query()->count())->toBe(1);
        expect(TenantAwareModel::query()->first()->name)->toBe('Record A');
    });

    it('does not apply scope when shouldScope returns false', function (): void {
        $tenant = TestTenant::create(['name' => 'Tenant A']);
        $context = app(TenantContext::class);
        $context->setScoped(false);
        $context->setTenantKeys([$tenant->getKey()]);

        TenantAwareModel::create(['name' => 'Record A', 'tenant_id' => $tenant->getKey()]);
        TenantAwareModel::create(['name' => 'Record B', 'tenant_id' => 999]);

        expect(TenantAwareModel::query()->count())->toBe(2);
    });

    it('returns no results when tenantKeys is empty array', function (): void {
        $context = app(TenantContext::class);
        $context->setScoped(true);
        $context->setTenantKeys([]);

        TenantAwareModel::create(['name' => 'Record A', 'tenant_id' => 1]);

        expect(TenantAwareModel::query()->count())->toBe(0);
    });

    it('uses custom tenant key from model when getTenantKeyName exists', function (): void {
        $context = app(TenantContext::class);
        $context->setScoped(true);
        $context->setTenantKeys([11]);

        CustomTenantKeyModel::create(['name' => 'Record A', 'tenant_id' => 11]);
        CustomTenantKeyModel::create(['name' => 'Record B', 'tenant_id' => 12]);

        expect(CustomTenantKeyModel::query()->count())->toBe(1);
    });
});
