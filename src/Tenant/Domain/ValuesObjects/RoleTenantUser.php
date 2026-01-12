<?php


namespace Src\Tenant\Domain\ValuesObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class RoleTenantUser extends StringValueObject{

    const ROLE_OWNER = 'owner';
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_STAFF = 'staff';

     private const ALLOWED_TYPES = [
        self::ROLE_OWNER,
        self::ROLE_ADMIN,
        self::ROLE_MANAGER,
        self::ROLE_STAFF
    ];

    private const HIERARCHY = [
        self::ROLE_OWNER => 3,
        self::ROLE_ADMIN => 2,
        self::ROLE_MANAGER => 1,
        self::ROLE_STAFF => 0,
    ];

    protected function validate(string $value): void
    {
        if (!in_array($value, self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException("Tipo rol del tenant no válido: {$value}", 400);
        }
    }

    public static function make(string $value): self {
        return new self($value);
    }

    public function isTenantOwner(): bool
    {
        return $this->value === self::ROLE_OWNER;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ROLE_ADMIN;
    }

    public function isManager(): bool
    {
        return $this->value === self::ROLE_MANAGER;
    }

    public function isStaff(): bool
    {
        return $this->value === self::ROLE_STAFF;
    }

    public function hasHigherOrEqualPrivilegesThan(self $other): bool
    {
        return self::HIERARCHY[$this->value] >= self::HIERARCHY[$other->value];
    }

    static public function owner(): self
    {
        return new self(self::ROLE_OWNER);
    }

    static public function admin(): self
    {
        return new self(self::ROLE_ADMIN);
    }

    static public function manager(): self
    {
        return new self(self::ROLE_MANAGER);
    }

    static public function staff(): self
    {
        return new self(self::ROLE_STAFF);
    }





}

?>
