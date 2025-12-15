<?php

declare(strict_types=1);

namespace Src\Shared\ValuesObjects;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Timezone Value Object
 *
 * Representa una zona horaria válida según el estándar IANA (Internet Assigned Numbers Authority).
 * Garantiza la validez del timezone en todo momento y proporciona métodos útiles para
 * conversiones y operaciones con fechas.
 *
 * Este es un Value Object inmutable: una vez creado, no puede modificarse.
 *
 * @package Src\Shared\ValuesObjects;
 * @author Gabriel
 * @version 1.0.0
 */
class Timezone
{
    /**
     * Constructor privado para forzar el uso del método factory `make()`.
     *
     * @param string $value Nombre del timezone IANA (ej: 'America/New_York', 'Europe/Madrid')
     * @param DateTimeZone $dateTimeZone Instancia de DateTimeZone de PHP
     *
     * @internal Usar Timezone::make() en lugar del constructor directo
     */
    private function __construct(
        private string $value,
        private DateTimeZone $dateTimeZone
    ) {}

    /**
     * Factory method principal para crear instancias válidas de Timezone.
     *
     * Valida que el timezone sea válido según la lista de identificadores IANA
     * y crea una instancia inmutable del objeto.
     *
     * @param string $timezone Nombre del timezone IANA a crear
     * @return self Instancia inmutable de Timezone
     *
     * @throws InvalidArgumentException Si el timezone está vacío o no es válido
     *
     * @example
     * $nyTimezone = Timezone::make('America/New_York');
     * $utcTimezone = Timezone::make('UTC');
     * $madridTimezone = Timezone::make('Europe/Madrid');
     */
    public static function make(string $timezone): self
    {
        self::ensureIsValidTimezone($timezone);
        return new self($timezone, new DateTimeZone($timezone));
    }

    /**
     * Obtiene el nombre del timezone como string.
     *
     * @return string Nombre IANA del timezone (ej: 'America/New_York')
     *
     * @example
     * $tz = Timezone::make('Europe/Madrid');
     * echo $tz->value(); // 'Europe/Madrid'
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * Obtiene la instancia de DateTimeZone interno.
     *
     * Útil para integración con otras APIs de PHP que requieren DateTimeZone.
     *
     * @return DateTimeZone Instancia de DateTimeZone de PHP
     *
     * @example
     * $tz = Timezone::make('Asia/Tokyo');
     * $phpDateTimeZone = $tz->dateTimeZone();
     */
    public function dateTimeZone(): DateTimeZone
    {
        return $this->dateTimeZone;
    }

    /**
     * Magic method para conversión implícita a string.
     *
     * Permite usar el objeto Timezone como string en contextos que lo requieran.
     *
     * @return string Nombre del timezone
     *
     * @example
     * $tz = Timezone::make('America/Chicago');
     * echo "Timezone: $tz"; // "Timezone: America/Chicago"
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Valida que un string sea un timezone IANA válido.
     *
     * @param string $timezone Timezone a validar
     * @return void
     *
     * @throws InvalidArgumentException Si el timezone está vacío o no es válido
     *
     * @internal Método privado usado internamente por make()
     */
    private static function ensureIsValidTimezone(string $timezone): void
    {
        if (empty($timezone)) {
            throw new InvalidArgumentException('El timezone no puede estar vacío');
        }

        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $commonExamples = array_keys(self::commonTimezones());
            throw new InvalidArgumentException(
                "El timezone '{$timezone}' no es válido. " .
                "Ejemplos válidos: " . implode(', ', array_slice($commonExamples, 0, 5)) . "..."
            );
        }
    }

    /**
     * Obtiene el offset UTC actual del timezone en formato ±HH:MM.
     *
     * El offset representa la diferencia horaria con UTC en el momento actual,
     * considerando el horario de verano (DST) si aplica.
     *
     * @return string Offset en formato ±HH:MM (ej: '-04:00', '+05:30')
     *
     * @example
     * $tz = Timezone::make('America/New_York');
     * echo $tz->offset(); // '-04:00' (en horario de verano)
     */
    public function offset(): string
    {
        $offset = $this->dateTimeZone->getOffset(new DateTime());
        $hours = intdiv($offset, 3600);
        $minutes = intdiv($offset % 3600, 60);

        return sprintf('%+03d:%02d', $hours, abs($minutes));
    }

    /**
     * Verifica si el timezone está actualmente en horario de verano (DST).
     *
     * @return bool True si está en horario de verano, false en caso contrario
     *
     * @example
     * $tz = Timezone::make('America/New_York');
     * echo $tz->isDst() ? 'Está en horario de verano' : 'No está en horario de verano';
     */
    public function isDst(): bool
    {
        $date = new DateTime('now', $this->dateTimeZone);
        return (bool) $date->format('I');
    }

    /**
     * Obtiene el nombre del timezone para mostrar.
     *
     * @return string Nombre completo del timezone
     *
     * @example
     * $tz = Timezone::make('America/New_York');
     * echo $tz->displayName(); // 'America/New_York'
     */
    public function displayName(): string
    {
        return $this->dateTimeZone->getName();
    }

    /**
     * Convierte una fecha UTC a la zona horaria representada por este objeto.
     *
     * @param DateTime $utcDate Fecha en UTC a convertir
     * @return DateTime Fecha convertida a esta zona horaria
     *
     * @example
     * $utcDate = new DateTime('2024-01-15 12:00:00', new DateTimeZone('UTC'));
     * $nyTz = Timezone::make('America/New_York');
     * $localDate = $nyTz->convertFromUTC($utcDate);
     * echo $localDate->format('Y-m-d H:i:s'); // '2024-01-15 07:00:00' (UTC-5)
     */
    public function convertFromUTC(DateTime $utcDate): DateTime
    {
        $utcDate->setTimezone($this->dateTimeZone);
        return $utcDate;
    }

    /**
     * Convierte una fecha de esta zona horaria a UTC.
     *
     * @param DateTime $localDate Fecha en esta zona horaria a convertir
     * @return DateTime Fecha convertida a UTC
     *
     * @example
     * $nyTz = Timezone::make('America/New_York');
     * $localDate = new DateTime('2024-01-15 07:00:00', $nyTz->dateTimeZone());
     * $utcDate = $nyTz->convertToUTC($localDate);
     * echo $utcDate->format('Y-m-d H:i:s'); // '2024-01-15 12:00:00'
     */
    public function convertToUTC(DateTime $localDate): DateTime
    {
        $localDate->setTimezone(new DateTimeZone('UTC'));
        return $localDate;
    }

    /**
     * Obtiene la fecha y hora actual en esta zona horaria.
     *
     * @return DateTime Fecha y hora actual en esta zona horaria
     *
     * @example
     * $tz = Timezone::make('Asia/Tokyo');
     * $now = $tz->now();
     * echo $now->format('Y-m-d H:i:s'); // Hora actual en Tokio
     */
    public function now(): DateTime
    {
        return new DateTime('now', $this->dateTimeZone);
    }

    /**
     * Compara si este timezone es igual a otro.
     *
     * @param self $other Otro objeto Timezone a comparar
     * @return bool True si son el mismo timezone, false en caso contrario
     *
     * @example
     * $tz1 = Timezone::make('Europe/Madrid');
     * $tz2 = Timezone::make('Europe/Paris');
     * $tz3 = Timezone::make('Europe/Madrid');
     *
     * $tz1->equals($tz2); // false
     * $tz1->equals($tz3); // true
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value();
    }

    /**
     * Verifica si este timezone tiene el mismo offset que otro en el momento actual.
     *
     * Nota: Dos timezones diferentes pueden tener el mismo offset en ciertos momentos
     * (ej: 'America/New_York' y 'America/Toronto' comparten offset en horario estándar).
     *
     * @param self $other Otro objeto Timezone a comparar
     * @return bool True si tienen el mismo offset actual, false en caso contrario
     *
     * @example
     * $ny = Timezone::make('America/New_York');
     * $toronto = Timezone::make('America/Toronto');
     * $ny->sameOffsetAs($toronto); // true (mismo offset en horario estándar)
     */
    public function sameOffsetAs(self $other): bool
    {
        return $this->offset() === $other->offset();
    }

    /**
     * Obtiene la lista de países que utilizan este timezone.
     *
     * @return array Lista de códigos de países ISO 3166-1 alpha-2
     *
     * @example
     * $tz = Timezone::make('Europe/Madrid');
     * $countries = $tz->countries();
     * // ['ES', 'AD'] (España y Andorra)
     */
    public function countries(): array
    {
        return DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $this->value);
    }

    /**
     * Devuelve un array de timezones comunes con sus nombres para mostrar.
     *
     * Este método es útil para crear selectores en interfaces de usuario
     * donde los usuarios necesitan elegir un timezone.
     *
     * @return array Array asociativo donde la clave es el timezone IANA
     *               y el valor es el nombre para mostrar
     *
     * @example
     * $common = Timezone::commonTimezones();
     * // [
     * //   'UTC' => 'UTC (Tiempo Universal Coordinado)',
     * //   'America/New_York' => 'Eastern Time (US & Canada)',
     * //   'Europe/Madrid' => 'Madrid, Spain',
     * //   ...
     * // ]
     */
    public static function commonTimezones(): array
    {
        return [
            'UTC'                   => 'UTC (Tiempo Universal Coordinado)',
            'America/Caracas'       => 'America, Caracas',
            'America/New_York'      => 'Eastern Time (US & Canada)',
            'America/Chicago'       => 'Central Time (US & Canada)',
            'America/Denver'        => 'Mountain Time (US & Canada)',
            'America/Los_Angeles'   => 'Pacific Time (US & Canada)',
            'America/Mexico_City'   => 'Mexico City',
            'America/Bogota'        => 'Bogotá, Lima',
            'America/Sao_Paulo'     => 'Brasilia',
            'Europe/London'         => 'London, Dublin',
            'Europe/Paris'          => 'Paris, Madrid',
            'Europe/Berlin'         => 'Berlin, Rome',
            'Europe/Moscow'         => 'Moscow, St. Petersburg',
            'Asia/Dubai'            => 'Dubai, Abu Dhabi',
            'Asia/Kolkata'          => 'India Standard Time',
            'Asia/Shanghai'         => 'Beijing, Shanghai',
            'Asia/Tokyo'            => 'Tokyo, Osaka',
            'Australia/Sydney'      => 'Sydney, Melbourne',
        ];
    }

    /**
     * Verifica si un string representa un timezone válido.
     *
     * Este método es una versión que no lanza excepciones de `make()`.
     * Útil para validaciones donde no se necesita crear el objeto.
     *
     * @param string $timezone Timezone a validar
     * @return bool True si el timezone es válido, false en caso contrario
     *
     * @example
     * Timezone::isValid('America/New_York'); // true
     * Timezone::isValid('Invalid/Timezone'); // false
     */
    public static function isValid(string $timezone): bool
    {
        try {
            self::make($timezone);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
