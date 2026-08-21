<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\SupportTicket\Application\UseCase\ListUserSupportTicketsUseCase;
use Src\SupportTicket\Infrastructure\Http\Support\ResolvesSupportRequester;

final class ViewCustomerSupportGETController extends Controller
{
    use ResolvesSupportRequester;

    public function __construct(
        private readonly ListUserSupportTicketsUseCase $listTicketsUseCase
    ) {}

    public function __invoke(Request $request): Response
    {
        // Leía de session('customer_id'), una clave que nunca se escribe en
        // ningún lugar del proyecto (el login por SSO fija
        // 'central_customer_id', ver ConsumeSsoTokenPOSTController), y caía
        // en la cabecera X-User-Id o en ?user_id= del cliente. Cualquiera
        // podía ver los tickets —y adjuntos— de otra persona con sólo
        // conocer su ID (hallazgo A6).
        $requester = $this->resolveSupportRequester($request);
        $userId = $requester['id'] ?? '';

        $status = $request->query('status');

        $ticketsData = $userId !== ''
            ? $this->listTicketsUseCase->execute($userId, $status ? (string) $status : null)
            : ['tickets' => [], 'counts' => [], 'pagination' => []];

        return Inertia::render('customer/support/CustomerSupportPage', [
            'title' => 'Centro de Ayuda & Soporte - OwOMarket',
            'user_id' => $userId,
            'tickets_data' => $ticketsData,
        ]);
    }
}
