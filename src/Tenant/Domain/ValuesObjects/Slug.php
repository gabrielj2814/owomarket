<?php

namespace Src\Tenant\Domain\ValuesObjects;

use InvalidArgumentException;

class Slug
{

    private function __construct(
        private string $value,
        private string $domain,
    ) {
        $this->ensureIsValidSlug($value);
        $this->ensureIsValidSubdomain($value);
    }

    /**
     * Método estático para crear un Slug
     */
    public static function make(string $value, string $domain): self
    {
        return new self($value, $domain);
    }

    /**
     * Crea un slug desde un string (ej: "Mi Tienda" -> "mi-tienda")
     */
    public static function fromString(string $string, string $domain): self
    {
        $slug = self::slugify($string);
        return new self($slug, $domain);
    }

    /**
     * Obtiene el valor del slug
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Obtiene el slug como subdominio completo
     */
    public function asSubdomain(): string
    {
        return "{$this->value}.{$this->domain}";
    }

    /**
     * Obtiene la URL completa del tenant
     */
    public function tenantUrl(string $protocol = 'https'): string
    {
        return "{$protocol}://{$this->value}.{$this->domain}";
    }

    /**
     * Verifica si dos slugs son iguales
     */
    public function equals(Slug $other): bool
    {
        return $this->value === $other->value();
    }

    /**
     * Magic method para convertir a string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Valida que sea un slug válido
     */
    private function ensureIsValidSlug(string $slug): void
    {
        if (empty($slug)) {
            throw new InvalidArgumentException('El slug no puede estar vacío',400);
        }

        if (strlen($slug) < 3) {
            throw new InvalidArgumentException('El slug debe tener al menos 3 caracteres',400);
        }

        if (strlen($slug) > 63) {
            throw new InvalidArgumentException('El slug no puede tener más de 63 caracteres',400);
        }

        // Validar formato: solo letras minúsculas, números y guiones
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            throw new InvalidArgumentException(
                'El slug solo puede contener letras minúsculas, números y guiones medios. ' .
                'No puede empezar ni terminar con guión.',400
            );
        }

        // Validar que no tenga guiones consecutivos
        if (str_contains($slug, '--')) {
            throw new InvalidArgumentException('El slug no puede tener guiones consecutivos',400);
        }

        // Validar que no sea un número
        if (is_numeric($slug)) {
            throw new InvalidArgumentException('El slug no puede ser solo números',400);
        }
    }

    /**
     * Valida que sea un subdominio válido
     */
    private function ensureIsValidSubdomain(string $slug): void
    {
        // Palabras reservadas para subdominios
        $reservedSubdomains = [
            'www', 'admin', 'api', 'app', 'beta', 'blog', 'dev',
            'mail', 'ftp', 'webmail', 'cpanel', 'whm', 'webdisk',
            'ns1', 'ns2', 'smtp', 'pop', 'imap', 'test', 'stage',
            'static', 'assets', 'cdn', 'm', 'mobile', 'support',
            'help', 'docs', 'status', 'shop', 'store', 'dashboard',
            'central', 'marketplace', 'login', 'auth', 'register'
        ];

        if (in_array($slug, $reservedSubdomains, true)) {
            throw new InvalidArgumentException(
                "El slug '{$slug}' es una palabra reservada y no puede usarse como subdominio",400
            );
        }

        // Validar que no contenga palabras ofensivas (opcional)
        $offensiveWords = $this->getOffensiveWords();
        foreach ($offensiveWords as $word) {
            if (str_contains($slug, $word)) {
                throw new InvalidArgumentException(
                    "El slug contiene palabras no permitidas",400
                );
            }
        }

        // Validar longitud para DNS (RFC 1035)
        $totalLength = strlen($slug) + 1 + strlen(config('app.domain'));
        if ($totalLength > 253) {
            throw new InvalidArgumentException(
                'El subdominio completo excede la longitud máxima permitida para DNS',400
            );
        }
    }

    /**
     * Convierte un string en slug
     */
    private static function slugify(string $string): string
    {
        // Convertir a minúsculas
        $slug = mb_strtolower($string, 'UTF-8');

        // Reemplazar caracteres especiales
        $slug = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $slug
        );

        // Eliminar caracteres no alfanuméricos (excepto espacios y guiones)
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);

        // Reemplazar múltiples espacios o guiones por uno solo
        $slug = preg_replace('/[\s-]+/', '-', $slug);

        // Eliminar espacios/guiones al inicio y final
        $slug = trim($slug, '-');

        // Si el slug queda vacío, generar uno aleatorio
        if (empty($slug)) {
            $slug = 'tenant-' . substr(md5($string), 0, 8);
        }

        return $slug;
    }

    /**
     * Lista de palabras ofensivas (puedes ampliar esta lista)
     */
    private function getOffensiveWords(): array
    {
        return [
            'admin', 'administrator', 'root', 'sysadmin',
            'fuck', 'shit', 'ass', 'bitch', 'bastard',
            'sex', 'porn', 'xxx', 'adult', 'nsfw'
        ];
    }

    /**
     * Métodos útiles adicionales
     */

    /**
     * Verifica si el slug es seguro para usar
     */
    public function isSafe(): bool
    {
        try {
            $this->ensureIsValidSubdomain($this->value);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Sugiere una versión alternativa del slug
     */
    public function suggestAlternative(): string
    {
        $base = $this->value;
        $suffix = 1;

        while (true) {
            $alternative = $base . '-' . $suffix;
            try {
                new self($alternative, $this->domain);
                return $alternative;
            } catch (InvalidArgumentException) {
                $suffix++;
            }
        }
    }

    /**
     * Genera un slug aleatorio para pruebas
     */
    public static function random(string $domain): self
    {
        $adjectives = ['happy', 'smart', 'quick', 'bright', 'bold', 'calm'];
        $nouns = ['fox', 'wolf', 'bear', 'eagle', 'lion', 'tiger'];
        $number = rand(1, 999);

        $randomString = $adjectives[array_rand($adjectives)] .
                       '-' . $nouns[array_rand($nouns)] .
                       '-' . $number;

        return self::make($randomString, $domain);
    }
}

?>
