<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

class OtherTenant extends Model
{
    protected $table = 'tenants';

    protected $guarded = [];

    public $timestamps = false;
}
