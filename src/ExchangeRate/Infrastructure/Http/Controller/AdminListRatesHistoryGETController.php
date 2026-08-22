<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\ExchangeRate\Application\UseCase\ListExchangeRatesHistoryUseCase;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\Shared\Helper\ApiResponse;

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

        // Hallazgo N37: esto ni siquiera usaba el sobre estandar —devolvia `success: true`
        // en vez de `status` y `code`— asi que un consumidor generico no podia leerlo.
        return ApiResponse::paginated(
            data: $mappedData,
            total: $result['total'],
            currentPage: $result['current_page'],
            perPage: $result['per_page'],
            lastPage: $result['last_page'],
            message: 'Historial de tasas consultado exitosamente'
        );
    }
}
