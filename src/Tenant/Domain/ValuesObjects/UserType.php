<?php

namespace Src\Tenant\Domain\ValuesObjects;


use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class UserType extends StringValueObject
{

    public const SUPER_ADMIN = 'super_admin';
    public const TENANT_OWNER = 'tenant_owner';
    public const TENANT_STAFF = 'tenant_staff';
    public const CUSTOMER = 'customer';

    private const ALLOWED_TYPES = [
        self::SUPER_ADMIN,
        self::TENANT_OWNER,
        self::TENANT_STAFF,
        self::CUSTOMER,
    ];

    private const HIERARCHY = [
        self::SUPER_ADMIN => 3,
        self::TENANT_OWNER => 2,
        self::TENANT_STAFF => 1,
        self::CUSTOMER => 0,
    ];

    public static function make(string $value):self{
        return new self($value);
    }

    protected function validate(string $value): void
    {
        if (!in_array($value, self::ALLOWED_TYPES)) {
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

    public function isCustomer(): bool
    {
        return $this->value === self::CUSTOMER;
    }

    public function isEmployee(): bool
    {
        return $this->value === self::TENANT_STAFF;
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

    public static function tenantEmployee(): self
    {
        return new self(self::TENANT_STAFF);
    }

    public static function customer(): self
    {
        return new self(self::CUSTOMER);
    }
}
