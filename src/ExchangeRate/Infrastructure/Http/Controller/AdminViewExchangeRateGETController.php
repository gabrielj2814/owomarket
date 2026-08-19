<?php

declare(strict_types=1);

namespace Src\ExchangeRate\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ConsultAuthUserApiByUuid;
use Src\Admin\Infrastructure\Services\ApiGateway;
use Src\ExchangeRate\Application\UseCase\GetActiveExchangeRateUseCase;
use Src\ExchangeRate\Application\UseCase\ListExchangeRatesHistoryUseCase;
use Src\ExchangeRate\Domain\Entities\ExchangeRate;
use Src\Shared\Domain\ValueObjects\Uuid;

final class AdminViewExchangeRateGETController extends Controller
{
    public function __construct(
        protected ApiGateway $apiGateway,
        private readonly GetActiveExchangeRateUseCase $getActiveExchangeRateUseCase,
        private readonly ListExchangeRatesHistoryUseCase $listExchangeRatesHistoryUseCase
    ) {}

    public function __invoke(Request $request): Response
    {
        $userUuid = $request->route('user_uuid') ?? $request->user_uuid ?? ($request->user()?->id ? (string) $request->user()->id : null);
        $usuario = null;

        if ($userUuid) {
            try {
                $uuid = Uuid::make((string) $userUuid);
                $consultAuthUser = new ConsultAuthUserApiByUuid($this->apiGateway->auth());
                $usuario = $consultAuthUser->execute($uuid);
            } catch (Exception) {
                $usuario = null;
            }
        }

        // 1. Obtener tasa activa actual
        $activeRateData = null;
        try {
            $activeRate = $this->getActiveExchangeRateUseCase->execute();
            $activeRateData = [
                'id' => $activeRate->getId()->value(),
                'base_currency' => $activeRate->getBaseCurrency()->value(),
                'target_currency' => $activeRate->getTargetCurrency()->value(),
                'rate' => $activeRate->getRate()->value(),
                'formatted_rate' => $activeRate->getRate()->format(4),
                'source' => $activeRate->getSource()->value(),
                'rate_date' => $activeRate->getRateDate()->value(),
                'is_active' => $activeRate->isActive(),
                'updated_at' => $activeRate->getUpdatedAt()?->format('c'),
            ];
        } catch (Exception) {
            $activeRateData = null;
        }

        // 2. Obtener historial inicial
        $historyResult = $this->listExchangeRatesHistoryUseCase->execute(page: 1, perPage: 10);
        $historyData = array_map(function (ExchangeRate $item) {
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
        }, $historyResult['data']);

        return Inertia::render('admin/exchangeRate/ExchangeRateManagementPage', [
            'title' => 'Gestión de Tasa de Cambio (BCV) - OwOMarket',
            'user_id' => $usuario ? $usuario->getUserId()->value() : ($request->user()?->id ? (string) $request->user()->id : ''),
            'user_name' => $usuario ? $usuario->getName()->value() : ($request->user()?->name ?? 'Administrador'),
            'user_email' => $usuario ? $usuario->getEmail()->value() : ($request->user()?->email ?? ''),
            'user_type' => $usuario ? $usuario->getType()->value() : 'admin',
            'user_avatar' => $usuario ? $usuario->getAvatar()->value() : '',
            'active_rate' => $activeRateData,
            'history' => [
                'data' => $historyData,
                'total' => $historyResult['total'],
                'current_page' => $historyResult['current_page'],
                'per_page' => $historyResult['per_page'],
                'last_page' => $historyResult['last_page'],
            ],
        ]);
    }
}
