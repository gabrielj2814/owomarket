<?php

declare(strict_types=1);

namespace Src\SupportTicket\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\SupportTicket\Application\UseCase\ListUserSupportTicketsUseCase;

final class ViewCustomerSupportGETController extends Controller
{
    public function __construct(
        private readonly ListUserSupportTicketsUseCase $listTicketsUseCase
    ) {}

    public function __invoke(Request $request): Response
    {
        $userId = (string) (session('customer_id') 
            ?: $request->header('X-User-Id') 
            ?: $request->query('user_id') 
            ?: auth()->id());

        $status = $request->query('status');

        $ticketsData = $this->listTicketsUseCase->execute(
            $userId,
            $status ? (string) $status : null
        );

        return Inertia::render('customer/support/CustomerSupportPage', [
            'title' => 'Centro de Ayuda & Soporte - OwOMarket',
            'user_id' => $userId,
            'tickets_data' => $ticketsData,
        ]);
    }
}
