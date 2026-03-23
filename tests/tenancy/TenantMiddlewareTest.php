<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use SaeedHosan\Tenancy\Contracts\TenantResolver;
use SaeedHosan\Tenancy\Http\Middleware\TenantMiddleware;
use SaeedHosan\Tenancy\TenantContext;
use SaeedHosan\Tenancy\Tests\Models\TestTenant as TestTenantForMiddleware;
use SaeedHosan\Tenancy\Tests\Models\TestTenantResolver;
use SaeedHosan\Tenancy\Tests\Models\TestUser;

uses(RefreshDatabase::class);

beforeEach(function (): void {

    config()->set('tenancy.tenant_model', TestTenantForMiddleware::class);
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

it('allows guest requests without tenant scoping', function (): void {
    $context = app(TenantContext::class);
    $context->setScoped(true);
    $context->setTenantKeys([1]);
    $context->setTenant(TestTenantForMiddleware::create(['name' => 'Tenant A']));

    $resolver = new TestTenantResolver(
        shouldScope: false,
        userTenant: null,
        routeTenant: null,
        accessibleKeys: [1]
    );

    app()->instance(TenantResolver::class, $resolver);

    $request    = Request::create('/test', 'GET');
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
    $userTenant  = TestTenantForMiddleware::create(['name' => 'Tenant A']);
    $routeTenant = TestTenantForMiddleware::create(['name' => 'Tenant B']);

    $resolver = new TestTenantResolver(
        shouldScope: true,
        userTenant: $userTenant,
        routeTenant: $routeTenant,
        accessibleKeys: [$userTenant->getKey(), $routeTenant->getKey()]
    );

    app()->instance(TenantResolver::class, $resolver);

    $request    = Request::create('/test', 'GET');
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
    $userTenant  = TestTenantForMiddleware::create(['name' => 'Tenant A']);
    $routeTenant = TestTenantForMiddleware::create(['name' => 'Tenant B']);

    $resolver = new TestTenantResolver(
        shouldScope: true,
        userTenant: $userTenant,
        routeTenant: $routeTenant,
        accessibleKeys: [$userTenant->getKey()]
    );

    app()->instance(TenantResolver::class, $resolver);

    $request    = Request::create('/test', 'GET');
    $middleware = new TenantMiddleware;

    $middleware->handle($request, function () {
        return response('ok');
    });
})->throws(Illuminate\Auth\Access\AuthorizationException::class);

it('throws authorization exception when route tenant key is string and not in accessible keys', function (): void {
    $userTenant  = TestTenantForMiddleware::create(['name' => 'Tenant A']);
    $routeTenant = TestTenantForMiddleware::create(['name' => 'Tenant B']);

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
})->throws(Illuminate\Auth\Access\AuthorizationException::class);
