<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use SaeedHosan\Tenancy\TenantContext;
use SaeedHosan\Tenancy\Tests\Models\CustomTenantKeyModel;
use SaeedHosan\Tenancy\Tests\Models\TenantAwareModel;
use SaeedHosan\Tenancy\Tests\Models\TestTenant;

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
