<?php


namespace Src\Tenant\Infrastructure\Eloquent\Repositories;

use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\UpdatedAt;
use Src\Tenant\Application\Contracts\Repositories\TenantUserRepositoryInterface;
use Src\Tenant\Domain\Entities\TenantUser;
use Src\Tenant\Domain\ValuesObjects\RoleTenantUser;
use Src\Tenant\Domain\ValuesObjects\Uuid;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantUser as ModelsTenantUser;

class TenantUserRepository implements TenantUserRepositoryInterface {



    public function assignTenantToUser(TenantUser $tenantUser): TenantUser {
        $record= new ModelsTenantUser();
        $record->id = $tenantUser->getId()->value();
        $record->tenant_id = $tenantUser->getTenantId()->value();
        $record->user_id = $tenantUser->getUserId()->value();
        $record->role = $tenantUser->getRole()->value();
        $record->permissions = $tenantUser->getPermissions();
        $record->save();
        // Implementación para asignar un tenant a un usuario en la base de datos
        return $tenantUser; // Retorna el TenantUser asignado o null si falla
    }

    public function consultTenantUsersByUuid(Uuid $id): ?TenantUser {
        $record = ModelsTenantUser::where('id', $id->value())->first();
        if (!$record) {
            return null;
        }

        return TenantUser::reconstitute(
            Uuid::make($record->id),
            Uuid::make($record->tenant_id),
            Uuid::make($record->user_id),
            RoleTenantUser::make($record->role),
            $record->permissions,
            $record->created_at ? new CreatedAt($record->created_at) : null,
            $record->updated_at ? new UpdatedAt($record->updated_at) : null
        );
    }

    public function deleteTenantUserByUuid(Uuid $id): bool {
        $record = ModelsTenantUser::where('id', $id->value())->first();
        if (!$record) {
            return false;
        }

        $record->delete();
        return true;
    }


}


?>
