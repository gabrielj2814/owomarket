<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\ExchangeRate\Application\UseCase\ListExchangeRatesHistoryUseCase;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;

final class AdminListRatesHistoryGETController
{
    public function __construct(
        private readonly ListExchangeRatesHistoryUseCase $listExchangeRatesHistoryUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);
        $source = $request->query('source');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $result = $this->listExchangeRatesHistoryUseCase->execute(
            $page,
            $perPage,
            $source ? (string) $source : null,
            $dateFrom ? (string) $dateFrom : null,
            $dateTo ? (string) $dateTo : null
        );

        $mappedData = array_map(function (ExchangeRate $item) {
            return [
                'id' => $item->getId()->value(),
                'base_currency' => $item->getBaseCurrency()->value(),
                'target_currency' => $item->getTargetCurrency()->value(),
                'rate' => $item->getRate()->value(),
                'formatted_rate' => $item->getRate()->format(4),
                'source' => $item->getSource()->value(),
                'rate_date' => $item->getRateDate()->value(),
                'is_active' => $item->isActive(),
                'metadata' => $item->getMetadata(),
                'created_at' => $item->getCreatedAt()?->format('c'),
            ];
        }, $result['data']);

        return response()->json([
            'success' => true,
            'data' => $mappedData,
            'meta' => [
                'total' => $result['total'],
                'current_page' => $result['current_page'],
                'per_page' => $result['per_page'],
                'last_page' => $result['last_page'],
            ],
        ]);
    }
}
