<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Http\Controller;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Src\ExchangeRate\Application\UseCase\CreateManualExchangeRateUseCase;

final class AdminCreateManualRatePOSTController
{
    public function __construct(
        private readonly CreateManualExchangeRateUseCase $createManualExchangeRateUseCase
    ) {}

    public function __invoke(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'rate' => ['required', 'numeric', 'gt:0'],
            'rate_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $exchangeRate = $this->createManualExchangeRateUseCase->execute(
                rateValue: (float) $validated['rate'],
                rateDate: $validated['rate_date'] ?? null,
                note: $validated['note'] ?? null,
                adminUserId: $request->user()?->id ? (string) $request->user()->id : null
            );

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
                    'message' => 'Tasa de cambio manual registrada exitosamente.',
                    'data' => $data,
                ]);
            }

            return back()->with('success', "Tasa de cambio manual registrada exitosamente: Bs. {$exchangeRate->getRate()->format(4)}/USD");
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Error al registrar la tasa manual: {$e->getMessage()}",
                ], 422);
            }

            return back()->with('error', "Error al registrar la tasa manual: {$e->getMessage()}");
        }
    }
}
