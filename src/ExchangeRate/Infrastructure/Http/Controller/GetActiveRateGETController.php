<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Src\ExchangeRate\Application\UseCase\GetActiveExchangeRateUseCase;

final class GetActiveRateGETController
{
    public function __construct(
        private readonly GetActiveExchangeRateUseCase $getActiveExchangeRateUseCase
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $rate = $this->getActiveExchangeRateUseCase->execute();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $rate->getId()->value(),
                    'base_currency' => $rate->getBaseCurrency()->value(),
                    'target_currency' => $rate->getTargetCurrency()->value(),
                    'rate' => $rate->getRate()->value(),
                    'formatted_rate' => $rate->getRate()->format(4),
                    'source' => $rate->getSource()->value(),
                    'rate_date' => $rate->getRateDate()->value(),
                    'is_active' => $rate->isActive(),
                    'created_at' => $rate->getCreatedAt()?->format('c'),
                    'updated_at' => $rate->getUpdatedAt()?->format('c'),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
