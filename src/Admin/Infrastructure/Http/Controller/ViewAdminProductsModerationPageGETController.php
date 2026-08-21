<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListProductsForModerationUseCase;

final class ViewAdminProductsModerationPageGETController extends Controller
{
    public function __construct(
        private readonly ListProductsForModerationUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute([
            'tenant_id' => $request->query('tenant_id'),
            'is_visible' => $request->query('is_visible'),
            'is_featured' => $request->query('is_featured'),
            'search' => $request->query('search'),
            'per_page' => (int) ($request->query('per_page', 15)),
        ]);

        return Inertia::render('admin/catalog/AdminProductsModerationPage', [
            'title' => 'Moderación de Productos del Marketplace - OwOMarket',
            'user_id' => $user_uuid,
            'products_data' => $result['products'],
            'metrics' => $result['metrics'],
            'tenants_list' => $result['tenants'],
            'filters' => [
                'tenant_id' => $request->query('tenant_id', ''),
                'is_visible' => $request->query('is_visible', ''),
                'is_featured' => $request->query('is_featured', ''),
                'search' => $request->query('search', ''),
            ],
        ]);
    }
}
