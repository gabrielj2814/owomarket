<?php

namespace Src\Tenant\Infrastructure\Eloquent\Repositories;

use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Infrastructure\Eloquent\Models\User as TenantOwnerModel;

class TenantOwnerRepository implements TenantOwnerRepositoryInterface {


    public function createTenantOwner(TenantOwner $tenantOwner): TenantOwner {

        $record= new TenantOwnerModel();
        $record->id=$tenantOwner->getId()->value();
        $record->name=$tenantOwner->getName()->value();
        $record->email=$tenantOwner->getEmail()->value();
        $record->password=$tenantOwner->getPassword()->getHash();
        $record->type=$tenantOwner->getType()->value();
        $record->phone=$tenantOwner->getPhone()->value();
        $record->avatar=$tenantOwner->getAvatar()->value();
        $record->is_active=$tenantOwner->isActive();
        $record->created_at=$tenantOwner->getCreatedAt()->value();
        $record->updated_at=$tenantOwner->getUpdatedAt()->value();
        $record->save();

        return $tenantOwner;
    }


}



?>
