<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Domain\Contracts;

use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\ExchangeRate\Domain\ValueObjects\CurrencyCode;
use Src\Shared\Domain\ValueObjects\Uuid;

interface ExchangeRateRepositoryInterface
{
    /**
     * Guarda o actualiza una tasa de cambio.
     */
    public function save(ExchangeRate $exchangeRate): void;

    /**
     * Busca la tasa activa para un par de divisas (ej: USD -> VES).
     */
    public function findActive(CurrencyCode $baseCurrency, CurrencyCode $targetCurrency): ?ExchangeRate;

    /**
     * Busca una tasa de cambio por su ID único.
     */
    public function findById(Uuid $id): ?ExchangeRate;

    /**
     * Desactiva todas las tasas activas para un par de divisas antes de registrar una nueva.
     */
    public function deactivateAll(CurrencyCode $baseCurrency, CurrencyCode $targetCurrency): void;

    /**
     * Consulta el historial paginado de tasas de cambio con filtros opcionales.
     *
     * @return array{data: array<ExchangeRate>, total: int, current_page: int, per_page: int, last_page: int}
     */
    public function listHistory(
        int $page = 1,
        int $perPage = 15,
        ?string $source = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array;
}
