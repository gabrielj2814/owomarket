<?php

declare(strict_types=1);

namespace Src\Product\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Product\Application\UseCase\ConsultAuthUserApiByUuidUseCase;
use Src\Product\Domain\ValueObjects\Uuid;
use Src\Product\Infrastructure\Http\Services\ApiGateway;

final class ViewProductFormGETController extends Controller
{
    public function __construct(
        protected ApiGateway $apiGateway
    ) {}

    public function index(Request $request): Response
    {
        $fullUrl = request()->getSchemeAndHttpHost();
        $userUuid = (string) $request->user_uuid;
        $uuid = Uuid::make($userUuid);

        $consultAuthUserApiByUuid = new ConsultAuthUserApiByUuidUseCase($this->apiGateway->authTenant());
        $usuario = $consultAuthUserApiByUuid->execute($uuid, $fullUrl);

        $host = $request->getHost();
        $recordUuid = $request->route('record_uuid');

        return Inertia::render(
            'tenant/modules/product/FormProductPage',
            [
                'title' => $recordUuid ? 'Editar Producto - OwOMarket' : 'Nuevo Producto - OwOMarket',
                'user_id' => $usuario->getUserId()->value(),
                'record_uuid' => $recordUuid,
                'host' => $host,
                'user_name' => $usuario->getName()->value(),
            ]
        );
    }
}
