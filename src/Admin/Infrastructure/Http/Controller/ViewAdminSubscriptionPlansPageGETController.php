<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListSubscriptionPlansUseCase;

final class ViewAdminSubscriptionPlansPageGETController extends Controller
{
    public function __construct(
        private readonly ListSubscriptionPlansUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute();

        return Inertia::render('admin/plans/AdminSubscriptionPlansPage', [
            'title' => 'Planes de Suscripción y Tarifas B2B - OwOMarket',
            'user_id' => $user_uuid,
            'plans' => $result['plans'],
            'metrics' => $result['metrics'],
        ]);
    }
}
