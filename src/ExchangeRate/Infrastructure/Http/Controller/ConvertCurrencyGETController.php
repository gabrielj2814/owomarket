<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\ExchangeRate\Application\UseCase\ConvertCurrencyAmountUseCase;

final class ConvertCurrencyGETController
{
    public function __construct(
        private readonly ConvertCurrencyAmountUseCase $convertCurrencyAmountUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $amount = (float) $request->query('amount', 0.0);
        $from = strtoupper((string) $request->query('from', 'USD'));
        $to = strtoupper((string) $request->query('to', 'VES'));

        if ($from === 'USD' && $to === 'VES') {
            $conversion = $this->convertCurrencyAmountUseCase->usdToVes($amount);
        } else {
            $conversion = $this->convertCurrencyAmountUseCase->vesToUsd($amount);
        }

        return response()->json([
            'success' => true,
            'data' => $conversion,
        ]);
    }
}
