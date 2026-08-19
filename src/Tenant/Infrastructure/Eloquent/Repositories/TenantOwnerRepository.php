<?php

namespace Src\Tenant\Infrastructure\Eloquent\Repositories;

use Exception;
use Src\Shared\Domain\ValueObjects\CreatedAt;
use Src\Shared\Domain\ValueObjects\PhoneNumber;
use Src\Shared\Domain\ValueObjects\SoftDeleteAt;
use Src\Shared\Domain\ValueObjects\UpdatedAt;
use Src\Shared\Domain\ValueObjects\UserEmail;
use Src\Shared\Domain\ValueObjects\UserStatus;
use Src\Shared\Domain\ValueObjects\Uuid;
use Src\Tenant\Application\Contracts\Repositories\TenantOwnerRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantOwner;
use Src\Tenant\Domain\ValueObjects\AvatarUrl;
use Src\Tenant\Domain\ValueObjects\Password;
use Src\Tenant\Domain\ValueObjects\UserName;
use Src\Tenant\Domain\ValueObjects\UserType;
use Src\Tenant\Infrastructure\Eloquent\Models\User as TenantOwnerModel;

class TenantOwnerRepository implements TenantOwnerRepositoryInterface
{
    /**
     * Método createTenantOwner.
     */
    public function createTenantOwner(TenantOwner $tenantOwner): TenantOwner
    {

        $record = new TenantOwnerModel;
        $record->id = $tenantOwner->getId()->value();
        $record->name = $tenantOwner->getName()->value();
        $record->email = $tenantOwner->getEmail()->value();
        $record->password = $tenantOwner->getPassword()->getHash();
        $record->type = $tenantOwner->getType()->value();
        $record->phone = $tenantOwner->getPhone()->value();
        $record->avatar = $tenantOwner->getAvatar()->value();
        $record->is_active = $tenantOwner->isActive();
        $record->created_at = $tenantOwner->getCreatedAt()->value();
        $record->updated_at = $tenantOwner->getUpdatedAt()->value();
        $record->save();

        return $tenantOwner;
    }

    /**
     * Método deleteTenantOwner.
     */
    public function deleteTenantOwner(Uuid $id): bool
    {
        $record = TenantOwnerModel::where('id', $id->value())->where('type', '=', UserType::TENANT_OWNER)->first();
        if ($record) {
            $record->delete();

            return true;
        }

        return false;
    }

    /**
     * Método deleteForceTenantOwner.
     */
    public function deleteForceTenantOwner(Uuid $id): bool
    {
        $record = TenantOwnerModel::withTrashed()->where('id', $id->value())->where('type', '=', UserType::TENANT_OWNER)->first();
        if ($record) {
            $record->forceDelete();

            return true;
        }

        return false;
    }

    /**
     * Método consultTenantOwnerByUuid.
     */
    public function consultTenantOwnerByUuid(Uuid $id): TenantOwner
    {
        $record = TenantOwnerModel::where('id', $id->value())->where('type', '=', UserType::TENANT_OWNER)->first();
        if (! $record) {
            throw new Exception('El Tenant Owner no fue encontrado en la base de datos', 404);
        }

        $name = UserName::make($record->name);
        $email = UserEmail::make($record->email);
        $type = UserType::make($record->type);
        $phone = ($record->phone != null) ? PhoneNumber::make($record->phone) : null;
        $avatar = AvatarUrl::make($record->avatar);
        $status = UserStatus::make($record->is_active);
        $createdAt = CreatedAt::fromString($record->created_at);
        $updatedAt = UpdatedAt::fromString($record->updated_at);
        $softDeleteAt = ($record->deleted_at != null) ? SoftDeleteAt::fromString($record->deleted_at) : null;
        $password = Password::fromHash($record->password);
        $emailVerifiedAt = null;
        $pin = null;

        $tenantOwner = TenantOwner::reconstitute(
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

    /**
     * Método updatePersonalData.
     */
    public function updatePersonalData(TenantOwner $tenantOwner): TenantOwner
    {

        $record = TenantOwnerModel::where('id', $tenantOwner->getId()->value())->where('type', '=', UserType::TENANT_OWNER)->first();

        $record->name = $tenantOwner->getName()->value();
        $record->phone = $tenantOwner->getPhone()->value();

        $record->save();

        return $tenantOwner;
    }

    /**
     * Método updatePassword.
     */
    public function updatePassword(TenantOwner $tenantOwner): TenantOwner
    {

        $record = TenantOwnerModel::where('id', $tenantOwner->getId()->value())->where('type', '=', UserType::TENANT_OWNER)->first();

        $record->password = $tenantOwner->getPassword()->getHash();

        $record->save();

        return $tenantOwner;
    }
}
