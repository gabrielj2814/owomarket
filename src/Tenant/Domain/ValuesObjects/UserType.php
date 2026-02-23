<?php

namespace Src\Tenant\Domain\ValuesObjects;


use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class UserType extends StringValueObject
{
    // CENTRAL TYPES
    public const SUPER_ADMIN = 'super_admin';
    public const TENANT_OWNER = 'tenant_owner';
    public const CUSTOMER = 'customer';

    // TENANT TYPES
    public const OWNER = 'owner';
    public const STAFF = 'staff';

    private const ALLOWED_TYPES = [
        self::SUPER_ADMIN,
        self::TENANT_OWNER,
        self::CUSTOMER,
        self::OWNER,
        self::STAFF,
    ];

    private const HIERARCHY = [
        self::SUPER_ADMIN => 4,
        self::TENANT_OWNER => 3,
        self::OWNER => 2,
        self::STAFF => 1,
        self::CUSTOMER => 0,
    ];

    public static function make(string $value):self{
        return new self($value);
    }

    protected function validate(string $value): void
    {
        if (!in_array($value, self::ALLOWED_TYPES)) {
            throw new InvalidArgumentException("Tipo de usuario no válido: {$value}");
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
        return $this->isSuperAdmin() || $this->isTenantOwner();
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

    public static function customer(): self
    {
        return new self(self::CUSTOMER);
    }
}

