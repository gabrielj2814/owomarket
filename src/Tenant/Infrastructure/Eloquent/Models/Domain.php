<?php

namespace Src\Tenant\Infrastructure\Eloquent\Models;

use Stancl\Tenancy\Database\Models\Domain as ModelsDomain;

class Domain extends ModelsDomain {

    protected $connection = 'central';
    public $incrementing = false;
    protected $ketType = "string";

    public function tenant()
    {
        return $this->belongsTo(config('tenancy.tenant_model'));
    }


}

?>
