<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListCentralCustomersForAdminUseCase;

final class ViewAdminCustomersPageGETController extends Controller
{
    public function __construct(
        private readonly ListCentralCustomersForAdminUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute([
            'search' => $request->query('search'),
            'is_active' => $request->query('is_active'),
            'per_page' => (int) ($request->query('per_page', 15)),
        ]);

        return Inertia::render('admin/customers/AdminCustomersIndexPage', [
            'title' => 'Directorio Central de Clientes - OwOMarket',
            'user_id' => $user_uuid,
            'customers_data' => $result['customers'],
            'metrics' => $result['metrics'],
            'filters' => [
                'search' => $request->query('search', ''),
                'is_active' => $request->query('is_active', ''),
            ],
        ]);
    }
}
