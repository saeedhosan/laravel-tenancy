<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestTenantForContext extends Model
{
    public $timestamps = false;

    protected $table = 'tenants';

    protected $guarded = [];
}
