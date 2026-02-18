<?php


namespace Src\Tenant\Domain\Entities;

use Src\Shared\ValuesObjects\CreatedAt;
use Src\Shared\ValuesObjects\UpdatedAt;
use Src\Tenant\Domain\ValuesObjects\RoleTenantUser;
use Src\Tenant\Domain\ValuesObjects\Uuid;

class TenantUser {

    private Uuid                $id;
    private Uuid                $tenantId;
    private Uuid                $userId;
    private RoleTenantUser      $role;
    private ?array              $permissions;
    private ?CreatedAt          $createdAt;
    private ?UpdatedAt          $updatedAt;


    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        Uuid $userId,
        RoleTenantUser $role,
        ?array $permissions = null,
        ?CreatedAt $createdAt = null,
        ?UpdatedAt $updatedAt = null
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->role = $role;
        $this->permissions = $permissions;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }


    public static function create(
        Uuid $tenantId,
        Uuid $userId,
        RoleTenantUser $role,
        ?array $permissions = null,
        ?CreatedAt $createdAt = null,
    ): self {
        $id = Uuid::generate(); // Asumiendo que tienes un método para generar UUIDs
        return new self($id, $tenantId, $userId, $role, $permissions, $createdAt);
    }

        // Factory method - para reconstruir desde BD
    public static function reconstitute(
        Uuid $id,
        Uuid $tenantId,
        Uuid $userId,
        RoleTenantUser $role,
        ?array $permissions = null,
        ?CreatedAt $createdAt = null,
        ?UpdatedAt $updatedAt = null
    ): self {
        return new self($id, $tenantId, $userId, $role, $permissions, $createdAt, $updatedAt);
    }

    public function getId(): Uuid {
        return $this->id;
    }

    public function getTenantId(): Uuid {
        return $this->tenantId;
    }

    public function getUserId(): Uuid {
        return $this->userId;
    }

    public function getRole(): RoleTenantUser {
        return $this->role;
    }

    public function getPermissions(): ?array {
        return $this->permissions;
    }

    public function getCreatedAt(): ?CreatedAt {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?UpdatedAt {
        return $this->updatedAt;
    }


}


?>
