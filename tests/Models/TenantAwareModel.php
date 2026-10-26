<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;
use SaeedHosan\Tenancy\Concerns\HasTenant;

class TenantAwareModel extends Model
{
    use HasTenant;

    public $timestamps = false;

    protected $table = 'tenant_aware_models';

    protected $guarded = [];
}
