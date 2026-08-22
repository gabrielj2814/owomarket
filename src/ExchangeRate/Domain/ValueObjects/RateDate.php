<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\ValueObjects;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class RateDate
{
    private DateTimeImmutable $value;

    public function __construct(DateTimeImmutable|string $value)
    {
        if (is_string($value)) {
            $parsed = DateTimeImmutable::createFromFormat('Y-m-d', trim($value))
                ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', trim($value))
                ?: date_create_immutable($value);

            if ($parsed === false) {
                throw new InvalidArgumentException("Formato de fecha de tasa inválido: '{$value}'", 400);
            }

            $this->value = $parsed->setTime(0, 0, 0);
        } else {
            $this->value = $value->setTime(0, 0, 0);
        }
    }

    public static function make(DateTimeImmutable|string $value): self
    {
        return new self($value);
    }

    /**
     * Hoy, segun el calendario del NEGOCIO.
     *
     * Hallazgo Auditoria #4: `new DateTimeImmutable` resuelve con la zona por defecto de
     * PHP —UTC—, asi que entre las 20:00 y la medianoche de Caracas esto ya devolvia el
     * dia siguiente. Una tasa sincronizada a las 21:00 quedaba fechada manana, y el aviso
     * de tasa congelada (N20) contaba mal los dias de antiguedad.
     *
     * Es una fecha de calendario, no un instante, asi que se pregunta en la zona del
     * negocio. Lo almacenado sigue en UTC; ver la nota de `business_timezone` en
     * config/app.php.
     *
     * La zona llega como PARAMETRO y no se lee de `config()`: esto es dominio y no puede
     * depender del framework. Quien la resuelve es la capa de aplicacion.
     */
    public static function today(string $businessTimezone = 'America/Caracas'): self
    {
        return new self(new DateTimeImmutable('now', new DateTimeZone($businessTimezone)));
    }

    public function value(): string
    {
        return $this->value->format('Y-m-d');
    }

    public function toDateTime(): DateTimeImmutable
    {
        return $this->value;
    }

    public function format(string $format = 'd/m/Y'): string
    {
        return $this->value->format($format);
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
