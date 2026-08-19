<?php

namespace Src\Shared\Domain\ValueObjects;

use InvalidArgumentException;

final class UserType extends StringValueObject
{
    // CENTRAL TYPES
    public const SUPER_ADMIN = 'super_admin';

    public const TENANT_OWNER = 'tenant_owner';

    public const TENANT_STAFF = 'tenant_staff';

    public const CUSTOMER = 'customer';

    // TENANT TYPES
    public const OWNER = 'owner';

    public const STAFF = 'staff';

    private const ALLOWED_TYPES = [
        self::SUPER_ADMIN,
        self::TENANT_OWNER,
        self::TENANT_STAFF,
        self::CUSTOMER,
        self::OWNER,
        self::STAFF,
    ];

    private const HIERARCHY = [
        self::SUPER_ADMIN => 4,
        self::TENANT_OWNER => 3,
        self::OWNER => 2,
        self::TENANT_STAFF => 1,
        self::STAFF => 1,
        self::CUSTOMER => 0,
    ];

    public static function make(string $value): self
    {
        return new self($value);
    }

    protected function validate(string $value): void
    {
        if (! in_array($value, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException("Tipo de usuario no válido: {$value}", 400);
        }
    }

    public function isSuperAdmin(): bool
    {
        return $this->value === self::SUPER_ADMIN;
    }

    public function isTenantOwner(): bool
    {
        return $this->value === self::TENANT_OWNER;
    }

    public function isOwner(): bool
    {
        return $this->value === self::OWNER;
    }

    public function isStaff(): bool
    {
        return $this->value === self::STAFF || $this->value === self::TENANT_STAFF;
    }

    public function isEmployee(): bool
    {
        return $this->value === self::TENANT_STAFF || $this->value === self::STAFF;
    }

    public function isCustomer(): bool
    {
        return $this->value === self::CUSTOMER;
    }

    public function hasHigherOrEqualPrivilegesThan(self $other): bool
    {
        return self::HIERARCHY[$this->value] >= self::HIERARCHY[$other->value];
    }

    public function canManageUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isTenantOwner() || $this->isOwner();
    }

    public static function superAdmin(): self
    {
        return new self(self::SUPER_ADMIN);
    }

    public static function tenantOwner(): self
    {
        return new self(self::TENANT_OWNER);
    }

    public static function owner(): self
    {
        return new self(self::OWNER);
    }

    public static function staff(): self
    {
        return new self(self::STAFF);
    }

    public static function tenantEmployee(): self
    {
        return new self(self::TENANT_STAFF);
    }

    public static function customer(): self
    {
        return new self(self::CUSTOMER);
    }
}
