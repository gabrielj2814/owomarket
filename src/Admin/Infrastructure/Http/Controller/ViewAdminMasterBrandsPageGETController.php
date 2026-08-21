<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListMasterBrandsUseCase;

final class ViewAdminMasterBrandsPageGETController extends Controller
{
    public function __construct(
        private readonly ListMasterBrandsUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute([
            'search' => $request->query('search'),
            'is_active' => $request->query('is_active'),
            'per_page' => (int) ($request->query('per_page', 15)),
        ]);

        return Inertia::render('admin/catalog/AdminMasterBrandsPage', [
            'title' => 'Catálogo Maestro de Marcas - OwOMarket',
            'user_id' => $user_uuid,
            'brands_data' => $result['brands'],
            'metrics' => $result['metrics'],
            'filters' => [
                'search' => $request->query('search', ''),
                'is_active' => $request->query('is_active', ''),
            ],
        ]);
    }
}
