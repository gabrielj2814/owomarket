<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListMasterCategoriesUseCase;

final class ViewAdminMasterCategoriesPageGETController extends Controller
{
    public function __construct(
        private readonly ListMasterCategoriesUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute();

        return Inertia::render('admin/catalog/AdminMasterCategoriesPage', [
            'title' => 'Catálogo Maestro de Categorías - OwOMarket',
            'user_id' => $user_uuid,
            'categories_tree' => $result['tree'],
            'categories_flat' => $result['categories'],
            'metrics' => $result['metrics'],
        ]);
    }
}
