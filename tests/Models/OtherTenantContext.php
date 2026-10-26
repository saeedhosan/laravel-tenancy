<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

class OtherTenantContext extends Model
{
    public $timestamps = false;

    protected $table = 'tenants';

    protected $guarded = [];
}
