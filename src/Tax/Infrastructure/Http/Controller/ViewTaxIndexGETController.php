<?php

declare(strict_types=1);

namespace Src\Tax\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ViewTaxIndexGETController extends Controller
{
    public function index(Request $request): Response
    {
        $userUuid = (string) $request->route('user_uuid', '');
        $host = $request->getHost();

        return Inertia::render(
            'tenant/modules/tax/TaxIndexPage',
            [
                'title' => 'Impuestos - OwOMarket',
                'user_id' => $userUuid,
                'host' => $host,
                'user_name' => auth()->user()?->name ?? 'Usuario',
            ]
        );
    }
}
