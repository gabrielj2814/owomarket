<?php

namespace Src\Tenant\Infrastructure\Eloquent\Repositories;

use Exception;
use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\SoftDeleteAt;
use Src\Shared\ValuesObjects\UpdatedAt;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValuesObjects\AvatarUrl;
use Src\Tenant\Domain\ValuesObjects\Password;
use Src\Tenant\Domain\ValuesObjects\PhoneNumber;
use Src\Tenant\Domain\ValuesObjects\UserEmail;
use Src\Tenant\Domain\ValuesObjects\UserName;
use Src\Tenant\Domain\ValuesObjects\UserStatus;
use Src\Tenant\Domain\ValuesObjects\UserType;
use Src\Tenant\Domain\ValuesObjects\Uuid;
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

    public function deleteTenantOwner(Uuid $id): bool {
        $record= TenantOwnerModel::where('id',$id->value())->where("type","=",UserType::TENANT_OWNER)->first();
        if($record){
            $record->delete();
            return true;
        }
        return false;
    }

    public function deleteForceTenantOwner(Uuid $id): bool {
        $record= TenantOwnerModel::withTrashed()->where('id',$id->value())->where("type","=",UserType::TENANT_OWNER)->first();
        if($record){
            $record->forceDelete();
            return true;
        }
        return false;
    }

    public function consultTenantOwnerByUuid(Uuid $id): TenantOwner
    {
        $record= TenantOwnerModel::where('id',$id->value())->where("type","=",UserType::TENANT_OWNER)->first();
        if(!$record){
            throw new Exception("El Tenant Owner no fue encontrado en la base de datos",404);
        }

        $name=UserName::make($record->name);
        $email=UserEmail::make($record->email);
        $type=UserType::make($record->type);
        $phone=($record->phone!=null)?PhoneNumber::make($record->phone):null;
        $avatar=AvatarUrl::make($record->avatar);
        $status=UserStatus::make($record->is_active);
        $createdAt=CreatedAt::fromString($record->created_at);
        $updatedAt=UpdatedAt::fromString($record->updated_at);
        $softDeleteAt=($record->deleted_at!=null)?SoftDeleteAt::fromString($record->deleted_at):null;
        $password=Password::fromHash($record->password);
        $emailVerifiedAt=null;
        $pin=null;

        $tenantOwner= TenantOwner::reconstitute(
            $id,
            $name,
            $email,
            $password,
            $emailVerifiedAt,
            $pin,
            $type,
            $phone,
            $avatar,
            $status,
            $createdAt,
            $updatedAt,
            $softDeleteAt
        );

        return $tenantOwner;
    }

    public function updatePersonalData(TenantOwner $tenantOwner): TenantOwner {

        $record= TenantOwnerModel::where('id',$tenantOwner->getId()->value())->where("type","=",UserType::TENANT_OWNER)->first();

        $record->name=$tenantOwner->getName()->value();
        $record->phone=$tenantOwner->getPhone()->value();

        $record->save();

        return $tenantOwner;
    }

    public function updatePassword(TenantOwner $tenantOwner): TenantOwner {

        $record= TenantOwnerModel::where('id',$tenantOwner->getId()->value())->where("type","=",UserType::TENANT_OWNER)->first();

        $record->password=$tenantOwner->getPassword()->getHash();

        $record->save();

        return $tenantOwner;
    }


}



?>
