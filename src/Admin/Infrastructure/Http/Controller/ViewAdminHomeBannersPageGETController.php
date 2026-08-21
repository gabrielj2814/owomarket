<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListHomeBannersUseCase;

final class ViewAdminHomeBannersPageGETController extends Controller
{
    public function __construct(
        private readonly ListHomeBannersUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute();

        return Inertia::render('admin/cms/AdminHomeBannersPage', [
            'title' => 'Gestor de Banners y Campañas Home - OwOMarket',
            'user_id' => $user_uuid,
            'banners' => $result['banners'],
            'metrics' => $result['metrics'],
        ]);
    }
}
