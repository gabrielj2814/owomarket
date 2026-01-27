<?php

namespace Src\Tenant\Infrastructure\Eloquent\Models;

use Stancl\Tenancy\Database\Models\Domain as ModelsDomain;

class Domain extends ModelsDomain {

    protected $connection = 'central';
    public $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = "string";

    public function tenant()
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }


}

?>
