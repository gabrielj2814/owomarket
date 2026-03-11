<?php

namespace Src\Product\Domain\ValueObjects;

use Src\Shared\ValuesObjects\StringValueObject;

/**
 * Clase Sku - Value Object para representar un SKU (Stock Keeping Unit)
 *
 * Extiende de StringValueObject y añade validaciones específicas para SKUs
 * siguiendo el patrón Value Object de DDD.
 */
class Sku extends StringValueObject
{
    /**
     * Longitud mínima permitida para un SKU
     */
    private const MIN_LENGTH = 5;

    /**
     * Longitud máxima permitida para un SKU
     */
    private const MAX_LENGTH = 20;

    /**
     * Patrón regex para validar el formato del SKU
     * Permite letras mayúsculas, números, guiones y guiones bajos
     */
    private const VALID_PATTERN = '/^[A-Z0-9_-]+$/';

    /**
     * Constructor de la clase Sku
     *
     * @param string $value El valor del SKU
     * @throws InvalidArgumentException Si el SKU no pasa las validaciones
     */
    function __construct(string $value)
    {
        parent::__construct(strtoupper($value)); // Convertir a mayúsculas automáticamente
    }

    /**
     * Método factory para crear una instancia de Sku
     *
     * @param string $value El valor del SKU
     * @return self Nueva instancia de Sku
     * @throws InvalidArgumentException Si el SKU no pasa las validaciones
     */
    public static function create(string $value): self
    {
        return new self($value);
    }

    /**
     * Valida que el SKU cumpla con las reglas de negocio establecidas
     *
     * Reglas de validación:
     * - No puede estar vacío
     * - Debe tener entre MIN_LENGTH y MAX_LENGTH caracteres
     * - Solo puede contener letras mayúsculas, números, guiones y guiones bajos
     *
     * @param string $value El valor del SKU a validar
     * @throws InvalidArgumentException Si alguna validación falla
     */
    protected function validate(string $value): void
    {
        $this->ensureNotEmpty($value);
        $this->ensureValidLength($value);
        $this->ensureValidFormat($value);
    }

    /**
     * Verifica que el SKU no esté vacío
     *
     * @param string $value El valor del SKU a validar
     * @throws InvalidArgumentException Si el SKU está vacío
     */
    private function ensureNotEmpty(string $value): void
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('El SKU no puede estar vacío', 400);
        }
    }

    /**
     * Verifica que el SKU tenga una longitud válida
     *
     * @param string $value El valor del SKU a validar
     * @throws InvalidArgumentException Si la longitud no está dentro del rango permitido
     */
    private function ensureValidLength(string $value): void
    {
        $length = strlen($value);

        if ($length < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('El SKU debe tener al menos %d caracteres. Longitud actual: %d',
                    self::MIN_LENGTH, $length), 400
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('El SKU no puede tener más de %d caracteres. Longitud actual: %d',
                    self::MAX_LENGTH, $length), 400
            );
        }
    }

    /**
     * Verifica que el SKU tenga un formato válido
     *
     * @param string $value El valor del SKU a validar
     * @throws InvalidArgumentException Si el formato no es válido
     */
    private function ensureValidFormat(string $value): void
    {
        if (!preg_match(self::VALID_PATTERN, $value)) {
            throw new \InvalidArgumentException(
                'El SKU solo puede contener letras mayúsculas, números, guiones y guiones bajos', 400
            );
        }
    }

    /**
     * Obtiene el código de producto del SKU (primeros caracteres hasta el primer guión)
     * Ejemplo: "PROD-123-XL" -> "PROD"
     *
     * @return string|null El código de producto o null si no hay guión
     */
    public function getProductCode(): ?string
    {
        $parts = explode('-', $this->value);
        return $parts[0] ?? null;
    }

    /**
     * Obtiene las variantes del SKU (todo después del primer guión)
     * Ejemplo: "PROD-123-XL" -> "123-XL"
     *
     * @return string|null Las variantes o null si no hay guión
     */
    public function getVariants(): ?string
    {
        $parts = explode('-', $this->value, 2);
        return $parts[1] ?? null;
    }

    /**
     * Verifica si el SKU pertenece a una categoría específica
     *
     * @param string $categoryPrefix El prefijo de la categoría a verificar
     * @return bool True si el SKU comienza con el prefijo especificado
     */
    public function belongsToCategory(string $categoryPrefix): bool
    {
        return str_starts_with($this->value, strtoupper($categoryPrefix));
    }

    /**
     * Normaliza el SKU eliminando caracteres especiales y espacios
     *
     * @return string El SKU normalizado
     */
    public function normalize(): string
    {
        return preg_replace('/[^A-Z0-9]/', '', $this->value);
    }

    /**
     * Obtiene una versión del SKU para URL (slug)
     *
     * @return string El SKU formateado para URL
     */
    public function toSlug(): string
    {
        return str_replace('_', '-', $this->value);
    }
}
