<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use SaeedHosan\Tenancy\Contracts\TenantResolver;
use SaeedHosan\Tenancy\Exceptions\TenancyException;
use SaeedHosan\Tenancy\ServiceProvider;
use SaeedHosan\Tenancy\TenantContext;
use SaeedHosan\Tenancy\TenantResolver as TenancyTenantResolver;

class InvalidResolver {}

class ProviderTestTenant extends Model
{
    protected $table = 'tenants';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function (): void {
    app()->register(ServiceProvider::class);
});

it('binds tenant context as singleton', function (): void {
    $first = app(TenantContext::class);
    $second = app(TenantContext::class);

    expect($first)->toBe($second);
});

it('throws when resolver config is invalid', function (): void {
    config()->set('tenancy.resolver', 123);

    app(TenantResolver::class);
})->throws(TenancyException::class, 'Invalid tenancy.resolver config');

it('throws when resolver does not implement contract', function (): void {
    config()->set('tenancy.resolver', InvalidResolver::class);

    app(TenantResolver::class);

})->throws(TenancyException::class, 'must implement');

it('resolves resolver when config is valid', function (): void {
    config()->set('tenancy.resolver', TenancyTenantResolver::class);

    $resolver = app(TenantResolver::class);

    expect($resolver)->toBeInstanceOf(TenancyTenantResolver::class);
});
