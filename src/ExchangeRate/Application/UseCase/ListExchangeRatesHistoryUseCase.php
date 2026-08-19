<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Application\UseCase;

use Src\ExchangeRate\Domain\Contracts\ExchangeRateRepositoryInterface;

final class ListExchangeRatesHistoryUseCase
{
    public function __construct(
        private readonly ExchangeRateRepositoryInterface $repository
    ) {}

    /**
     * @return array{data: array, total: int, current_page: int, per_page: int, last_page: int}
     */
    public function execute(
        int $page = 1,
        int $perPage = 15,
        ?string $source = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): array {
        return $this->repository->listHistory($page, $perPage, $source, $dateFrom, $dateTo);
    }
}
