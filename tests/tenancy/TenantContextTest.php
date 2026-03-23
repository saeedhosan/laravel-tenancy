<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use SaeedHosan\Tenancy\TenantContext;

class TestTenantForContext extends Model
{
    public $timestamps = false;

    protected $table = 'tenants';

    protected $guarded = [];
}

class OtherTenantContext extends Model
{
    public $timestamps = false;

    protected $table = 'tenants';

    protected $guarded = [];
}

uses(RefreshDatabase::class);

beforeEach(function (): void {

    config()->set('tenancy.tenant_model', TestTenantForContext::class);
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

it('defaults accessible tenant keys to the current tenant', function (): void {
    $tenant = TestTenantForContext::create(['name' => 'Tenant A']);
    $context = app(TenantContext::class);

    $context->setTenant($tenant);

    expect($context->tenantKeys())->toBe([$tenant->getKey()]);
});

it('normalizes tenant keys and checks access when scoped', function (): void {
    $tenant = TestTenantForContext::create(['name' => 'Tenant A']);

    $context = app(TenantContext::class);
    $context->setScoped(true);
    $context->setTenantKeys([$tenant, '2', ' 2 ', 'alpha', null, '']);

    expect($context->tenantKeys())->toBe([$tenant->getKey(), 2, 'alpha']);
    expect($context->isTenantAccessible(2))->toBeTrue();
    expect($context->isTenantAccessible('alpha'))->toBeTrue();
    expect($context->isTenantAccessible(99))->toBeFalse();
});

it('allows all tenants when scoping is disabled', function (): void {
    $context = app(TenantContext::class);
    $context->setScoped(false);
    $context->setTenantKeys([1]);

    expect($context->isTenantAccessible(999))->toBeTrue();
});

it('rejects tenants that do not match the configured tenant model', function (): void {
    $context = app(TenantContext::class);

    $context->setTenant(new OtherTenantContext);
})->throws(InvalidArgumentException::class);

it('returns tenant via tenant() method', function (): void {
    $tenant = TestTenantForContext::create(['name' => 'Tenant A']);
    $context = app(TenantContext::class);

    $context->setTenant($tenant);

    expect($context->tenant()?->getKey())->toBe($tenant->getKey());
});

it('returns tenant via current() alias method', function (): void {
    $tenant = TestTenantForContext::create(['name' => 'Tenant A']);
    $context = app(TenantContext::class);

    $context->setTenant($tenant);

    expect($context->current()?->getKey())->toBe($tenant->getKey());
});

it('returns null when no tenant is set', function (): void {
    $context = app(TenantContext::class);

    expect($context->tenant())->toBeNull();
    expect($context->current())->toBeNull();
});

it('returns tenantKey from context', function (): void {
    $tenant = TestTenantForContext::create(['name' => 'Tenant A']);
    $context = app(TenantContext::class);

    $context->setTenant($tenant);

    expect($context->tenantKey())->toBe($tenant->getKey());
});

it('returns null tenantKey when no tenant is set', function (): void {
    $context = app(TenantContext::class);

    expect($context->tenantKey())->toBeNull();
});
