<?php

namespace Src\Authentication\Infrastructure\Eloquent\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;


class PersonalAccessToken extends SanctumPersonalAccessToken
{
    protected $connection = 'central';
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'tokenable_type',
        'tokenable_id', // Ahora será UUID
    ];
}


?>
