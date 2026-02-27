<?php

namespace Src\Product\Domain\ValueObjects;

use InvalidArgumentException;
use Src\Shared\ValuesObjects\StringValueObject;

final class AvatarUrl extends StringValueObject
{


    public static function make(string $value):self{
        return new self($value);
    }

    protected function validate(string $value): void
    {
        if (empty(trim($value))) {
            return; // Permitir null/empty
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("La URL del avatar no es válida");
        }

        $allowedSchemes = ['http', 'https'];
        $scheme = parse_url($value, PHP_URL_SCHEME);

        if (!in_array($scheme, $allowedSchemes)) {
            throw new InvalidArgumentException("El avatar debe usar HTTP o HTTPS");
        }

        // Validar extensión de archivo
        $path = parse_url($value, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array(strtolower($extension), $allowedExtensions)) {
            throw new InvalidArgumentException("Formato de imagen no permitido para el avatar");
        }

        if (strlen($value) > 500) {
            throw new InvalidArgumentException("La URL del avatar es demasiado larga");
        }
    }

    public static function fromFilename(string $filename, string $baseUrl): self
    {
        $url = rtrim($baseUrl, '/') . '/avatars/' . $filename;
        return new self($url);
    }

    public function getFilename(): ?string
    {
        if (empty($this->value)) {
            return null;
        }

        $path = parse_url($this->value, PHP_URL_PATH);
        return basename($path);
    }

    public function isDefault(): bool
    {
        return empty($this->value);
    }

    public static function default(): self
    {
        return new self('');
    }
}
