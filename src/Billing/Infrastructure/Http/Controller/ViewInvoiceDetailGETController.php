<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ViewInvoiceDetailGETController extends Controller
{
    public function index(Request $request): Response
    {
        $userUuid = (string) $request->route('user_uuid', '');
        $invoiceId = (string) $request->route('id', '');
        $host = $request->getHost();

        return Inertia::render(
            'tenant/modules/billing/ShowInvoiceDetailPage',
            [
                'title' => 'Detalle de Factura - OwOMarket',
                'user_id' => $userUuid,
                'invoice_id' => $invoiceId,
                'host' => $host,
                'user_name' => auth()->user()?->name ?? 'Usuario',
            ]
        );
    }
}
