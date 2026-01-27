<?php

namespace Src\Tenant\Infrastructure\Eloquent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use HasFactory;

    protected $connection = 'central';
    public $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = "string";

    protected $guarded = [];
}

