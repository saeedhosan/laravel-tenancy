<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use SaeedHosan\Tenancy\Concerns\HasTenant;

class CustomTenantKeyModel extends Model
{
    use HasTenant;

    public $timestamps = false;

    protected $table = 'custom_tenant_models';

    protected $guarded = [];

    public function getTenantKeyName(): string
    {
        return 'tenant_id';
    }
}
