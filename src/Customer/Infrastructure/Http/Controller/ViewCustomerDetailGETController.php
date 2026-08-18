<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ViewCustomerDetailGETController extends Controller
{
    public function index(Request $request): Response
    {
        $userUuid = (string) $request->route('user_uuid', '');
        $customerId = (string) $request->route('id', '');
        $host = $request->getHost();

        return Inertia::render(
            'tenant/modules/customer/ShowCustomerDetailPage',
            [
                'title' => 'Detalle de Cliente - OwOMarket',
                'user_id' => $userUuid,
                'customer_id' => $customerId,
                'host' => $host,
                'user_name' => auth()->user()?->name ?? 'Usuario',
            ]
        );
    }
}
