<?php

namespace Src\Tenant\Domain\ValuesObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class Domain extends StringValueObject
{
    private const MAX_LENGTH = 253;
    private const PART_MAX_LENGTH = 63;
    private const LOCAL_TLDS = ['local', 'localhost', 'test', 'example', 'invalid'];
    private const COMMON_TLDS = ['com', 'org', 'net', 'edu', 'gov', 'io', 'co', 'ai', 'es', 'mx', 'ar', 'br', 'cl', 'pe', 've'];

    public static function make(string $value): self
    {
        return new self(self::normalize($value));
    }

    protected function validate(string $value): void
    {
        $trimmedValue = trim($value);

        // Validación básica
        $this->validateNotEmpty($trimmedValue);
        $this->validateLength($trimmedValue);

        // Validación específica
        $parts = explode('.', strtolower($trimmedValue));
        $tld = end($parts);

        if (in_array($tld, self::LOCAL_TLDS)) {
            $this->validateLocalDomain($parts);
        } else {
            $this->validatePublicDomain($parts, $tld);
        }
    }

    private function validateNotEmpty(string $value): void
    {
        if (empty($value)) {
            throw new InvalidArgumentException("El dominio no puede estar vacío");
        }
    }

    private function validateLength(string $value): void
    {
        if (strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf("El dominio es demasiado largo (máximo %d caracteres)", self::MAX_LENGTH)
            );
        }
    }

    private function validateLocalDomain(array $parts): void
    {
        if (count($parts) < 2) {
            throw new InvalidArgumentException("El dominio local debe tener al menos 2 partes");
        }

        $this->validateDomainParts($parts, false);
    }

    private function validatePublicDomain(array $parts, string $tld): void
    {
        if (count($parts) < 2) {
            throw new InvalidArgumentException("El dominio público debe tener al menos 2 partes");
        }

        $this->validateTld($tld);
        $this->validateDomainParts($parts, true);
        $this->validateDomainName($parts[0]);
    }

    private function validateTld(string $tld): void
    {
        if (strlen($tld) < 2 || !ctype_alpha($tld)) {
            throw new InvalidArgumentException("El TLD debe tener al menos 2 letras");
        }

        // Opcional: Validar contra lista de TLDs conocidos
        if (!in_array($tld, self::COMMON_TLDS) && strlen($tld) < 3 && strlen($tld) !== 2) {
            throw new InvalidArgumentException(
                sprintf("TLD '%s' no es común. TLDs permitidos: %s",
                    $tld,
                    implode(', ', self::COMMON_TLDS)
                )
            );
        }
    }

    private function validateDomainParts(array $parts, bool $isPublic): void
    {
        foreach ($parts as $part) {
            if (empty($part)) {
                throw new InvalidArgumentException("Las partes del dominio no pueden estar vacías");
            }

            if (strlen($part) > self::PART_MAX_LENGTH) {
                throw new InvalidArgumentException(
                    sprintf("Cada parte del dominio no puede exceder %d caracteres", self::PART_MAX_LENGTH)
                );
            }

            $pattern = $isPublic
                ? '/^[a-z]([a-z0-9\-]*[a-z0-9])?$/'  // Público: empieza con letra
                : '/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/'; // Local: empieza con letra o número

            if (!preg_match($pattern, $part)) {
                $message = $isPublic
                    ? "Las partes del dominio público deben comenzar con letra"
                    : "Las partes del dominio solo pueden contener letras, números y guiones";
                throw new InvalidArgumentException($message);
            }

            if (str_contains($part, '--')) {
                throw new InvalidArgumentException("No se permiten guiones consecutivos");
            }
        }
    }

    private function validateDomainName(string $domainName): void
    {
        if (strlen($domainName) < 2) {
            throw new InvalidArgumentException("El nombre del dominio debe tener al menos 2 caracteres");
        }

        if (is_numeric($domainName)) {
            throw new InvalidArgumentException("El nombre del dominio no puede ser solo números");
        }
    }

    // Métodos de negocio (igual que antes)
    public function hasCommonTld(): bool
    {
        return in_array($this->getTld(), self::COMMON_TLDS);
    }

    public function isCountryTld(): bool
    {
        $tld = $this->getTld();
        return strlen($tld) === 2 && ctype_alpha($tld) && !$this->isLocal();
    }

    public function getTldType(): string
    {
        if ($this->isLocal()) return 'local';
        if ($this->hasCommonTld()) return 'common';
        if ($this->isCountryTld()) return 'country';
        return 'other';
    }

    public function getBaseDomain(): string
    {
        $parts = explode('.', $this->value);
        return count($parts) >= 2
            ? implode('.', array_slice($parts, -2))
            : $this->value;
    }

    public function getSubdomain(): ?string
    {
        $parts = explode('.', $this->value);
        if (count($parts) > 2) {
            $subdomain = implode('.', array_slice($parts, 0, -2));
            return !empty($subdomain) ? $subdomain : null;
        }
        return null;
    }

    public function isSubdomain(): bool
    {
        return $this->getSubdomain() !== null;
    }

    public function isLocal(): bool
    {
        return in_array($this->getTld(), self::LOCAL_TLDS);
    }

    public function isPublic(): bool
    {
        return !$this->isLocal();
    }

    public function getTld(): string
    {
        $parts = explode('.', $this->value);
        return end($parts);
    }

    public function getDomainName(): string
    {
        $parts = explode('.', $this->value);
        return $parts[0];
    }

    public function getParts(): array
    {
        return explode('.', $this->value);
    }

    public static function isValid(string $domain): bool
    {
        try {
            new self(self::normalize($domain));
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public static function normalize(string $domain): string
    {
        return strtolower(trim($domain));
    }

    public static function fromString(string $value): self
    {
        return new self(self::normalize($value));
    }
}
