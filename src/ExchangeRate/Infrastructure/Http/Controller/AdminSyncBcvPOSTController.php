<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Src\ExchangeRate\Application\UseCase\SyncBcvExchangeRateUseCase;

final class AdminSyncBcvPOSTController
{
    public function __construct(
        private readonly SyncBcvExchangeRateUseCase $syncBcvExchangeRateUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        try {
            $exchangeRate = $this->syncBcvExchangeRateUseCase->execute();

            $data = [
                'id' => $exchangeRate->getId()->value(),
                'base_currency' => $exchangeRate->getBaseCurrency()->value(),
                'target_currency' => $exchangeRate->getTargetCurrency()->value(),
                'rate' => $exchangeRate->getRate()->value(),
                'formatted_rate' => $exchangeRate->getRate()->format(4),
                'source' => $exchangeRate->getSource()->value(),
                'rate_date' => $exchangeRate->getRateDate()->value(),
                'is_active' => $exchangeRate->isActive(),
            ];

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tasa oficial del BCV sincronizada exitosamente.',
                    'data' => $data,
                ]);
            }

            return back()->with('success', "Tasa oficial del BCV sincronizada exitosamente: Bs. {$exchangeRate->getRate()->format(4)}/USD");
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Error al sincronizar con el BCV: {$e->getMessage()}",
                ], 500);
            }

            return back()->with('error', "Error al sincronizar con el BCV: {$e->getMessage()}");
        }
    }
}
