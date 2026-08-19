<?php

declare(strict_types=1);

namespace Src\Order\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ViewOrderDetailGETController extends Controller
{
    public function index(string $userUuid, string $id, Request $request): Response
    {
        $host = $request->getHost();

        return Inertia::render(
            'tenant/modules/order/ShowOrderDetailPage',
            [
                'title' => 'Detalle del Pedido - OwOMarket',
                'user_id' => $userUuid,
                'order_id' => $id,
                'host' => $host,
                'user_name' => auth()->user()?->name ?? 'Usuario',
            ]
        );
    }
}
