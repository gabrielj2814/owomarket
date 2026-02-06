<?php

namespace Src\Authentication\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

class AuthUser extends Model
{
    //

    public $table = 'auth_users';
    public $primaryKey = 'id';
    public $incrementing = false;
    protected $ketType = "string";

    protected $fillable = [
        'id',
        'user_id',
        'user_name',
        'user_email',
        'user_type',
        'user_avatar',
    ];
}
