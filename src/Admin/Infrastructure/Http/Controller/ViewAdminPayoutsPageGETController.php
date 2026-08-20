<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListCentralPayoutRequestsUseCase;

final class ViewAdminPayoutsPageGETController
{
    public function __construct(
        private readonly ListCentralPayoutRequestsUseCase $useCase
    ) {}

    public function index(Request $request, string $user_uuid): Response
    {
        $filters = [
            'status' => $request->query('status'),
            'payment_method' => $request->query('payment_method'),
            'search' => $request->query('search'),
            'page' => (int) $request->query('page', 1),
            'per_page' => 15,
        ];

        $result = $this->useCase->execute($filters);

        return Inertia::render('admin/payouts/AdminPayoutsIndexPage', [
            'title' => 'Gestión de Retiros y Liquidaciones - OwOMarket Admin',
            'user_id' => $user_uuid,
            'payouts' => $result['payouts'],
            'pagination' => $result['pagination'],
            'metrics' => $result['metrics'],
            'filters' => $filters,
        ]);
    }
}
