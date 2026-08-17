<?php

declare(strict_types=1);

namespace Src\Shipping\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ViewShippingIndexGETController extends Controller
{
    public function index(Request $request): Response
    {
        $userUuid = (string) $request->route('user_uuid', '');
        $host = $request->getHost();

        return Inertia::render(
            'tenant/modules/shipping/ShippingIndexPage',
            [
                'title' => 'Envíos - OwOMarket',
                'user_id' => $userUuid,
                'host' => $host,
                'user_name' => auth()->user()?->name ?? 'Usuario',
            ]
        );
    }
}
