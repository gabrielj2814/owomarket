<?php

namespace Src\Authentication\Infrastructure\Eloquent\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'tokenable_type',
        'tokenable_id', // Ahora será UUID
    ];
}
