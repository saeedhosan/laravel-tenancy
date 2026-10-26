<?php

declare(strict_types=1);

namespace Tests\Models;

use Illuminate\Foundation\Auth\User as AuthenticatableUser;

class TestUser extends AuthenticatableUser
{
    public $timestamps = false;

    protected $guarded = [];
}
