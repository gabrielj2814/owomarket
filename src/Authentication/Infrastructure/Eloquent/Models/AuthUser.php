<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthUser extends Model
{
    //
    protected $connection = 'central';
    public $table = 'auth_users';
    public $primaryKey = 'id';
    public $incrementing = false;
    protected $ketType = "string";
}
