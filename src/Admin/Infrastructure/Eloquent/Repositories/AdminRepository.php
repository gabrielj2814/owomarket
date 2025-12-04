<?php


namespace Src\Admin\Infrastructure\Eloquent\Repositories;

use Src\Admin\Application\Contracts\Repositories\AdminRepositoryInterface;
use Src\Admin\Domain\Etities\Admin;
use Src\Admin\Domain\ValueObjects\AvatarUrl;
use Src\Admin\Domain\ValueObjects\Password;
use Src\Admin\Domain\ValueObjects\PhoneNumber;
use Src\Admin\Domain\ValueObjects\UserEmail;
use Src\Admin\Domain\ValueObjects\UserName;
use Src\Admin\Domain\ValueObjects\UserStatus;
use Src\Admin\Domain\ValueObjects\UserType;
use Src\Admin\Domain\ValueObjects\Uuid;
use Src\Admin\Infrastructure\Eloquent\Models\User as AdminModel;
use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\UpdatedAt;

class AdminRepository implements AdminRepositoryInterface {



    public function create(Admin $admin): ?Admin
    {
        $record= new AdminModel();
        $record->id=$admin->getId()->value();
        $record->name=$admin->getName()->value();
        $record->email=$admin->getEmail()->value();
        $record->password=$admin->getPassword()->getHash();
        $record->type=$admin->getType()->value();
        $record->phone=$admin->getPhone()->value();
        $record->avatar=$admin->getAvatar()->value();
        $record->is_active=$admin->isActive();
        $record->created_at=$admin->getCreatedAt()->value();
        $record->updated_at=$admin->getUpdatedAt()->value();
        $record->save();

        return $admin;
    }

    public function consultByUuid(Uuid $uuid): ?Admin
    {
        $record= AdminModel::query()->where("id","=",$uuid->value())->first();
        if(!$record){
            return null;
        }

        $id=Uuid::make($record->id);
        $name=UserName::make($record->name);
        $email=UserEmail::make($record->email);
        $password=Password::fromHash($record->password);
        $phone=($record->phone!=null &&  $record->phone!="")?PhoneNumber::make($record->phone):null;
        $type=UserType::make($record->type);
        $avatar=AvatarUrl::make($record->avatar);
        $state=UserStatus::make($record->is_active);

        $create_at= CreatedAt::fromString($record->created_at);
        $update_at= UpdatedAt::fromString($record->updated_at);

        $admin= Admin::reconstitute(
            $id,
            $name,
            $email,
            $password,
            null,
            null,
            $type,
            $phone,
            $avatar,
            $state,
            $create_at,
            $update_at
        );

        return $admin;


    }

    public function consultByEmail(UserEmail $email): ?Admin {
        $record= AdminModel::query()
        ->where("email","=",$email->value())
        ->first();

        if(!$record){
            return null;
        }

        $id=Uuid::make($record->id);
        $name=UserName::make($record->name);
        $email=UserEmail::make($record->email);
        $password=Password::fromHash($record->password);
        $phone=($record->phone!=null &&  $record->phone!="")?PhoneNumber::make($record->phone):null;
        $type=UserType::make($record->type);
        $avatar=AvatarUrl::make($record->avatar);
        $state=UserStatus::make($record->is_active);

        $create_at= CreatedAt::fromString($record->created_at);
        $update_at= UpdatedAt::fromString($record->updated_at);

        $admin= Admin::reconstitute(
            $id,
            $name,
            $email,
            $password,
            null,
            null,
            $type,
            $phone,
            $avatar,
            $state,
            $create_at,
            $update_at
        );

        return $admin;


    }

    public function editar(Admin $admin): ?Admin
    {
        $record= AdminModel::query()
        ->where("id","=",$admin->getId()->value())
        ->where("type","=",UserType::SUPER_ADMIN)
        ->first();
        if(!$record){
            return null;
        }

        $record->name=$admin->getName()->value();
        $record->email=$admin->getEmail()->value();
        $record->type=$admin->getType()->value();
        $record->phone=$admin->getPhone()->value();
        $record->avatar=$admin->getAvatar()->value();
        $record->is_active=$admin->isActive();
        $record->updated_at=$admin->getUpdatedAt()->value();
        $record->save();

        return $admin;
    }


}



?>
