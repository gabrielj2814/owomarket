<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListCentralAuditLogsUseCase;

final class ViewAdminAuditLogsPageGETController extends Controller
{
    public function __construct(
        private readonly ListCentralAuditLogsUseCase $useCase
    ) {}

    public function __invoke(Request $request, string $user_uuid): Response
    {
        $result = $this->useCase->execute([
            'action' => $request->query('action'),
            'entity_type' => $request->query('entity_type'),
            'search' => $request->query('search'),
            'per_page' => (int) ($request->query('per_page', 20)),
        ]);

        return Inertia::render('admin/security/AdminAuditLogsPage', [
            'title' => 'Pista de Auditoría y Seguridad - OwOMarket',
            'user_id' => $user_uuid,
            'logs_data' => $result['logs'],
            'metrics' => $result['metrics'],
            'actions_list' => $result['actions_list'],
            'filters' => [
                'action' => $request->query('action', ''),
                'entity_type' => $request->query('entity_type', ''),
                'search' => $request->query('search', ''),
            ],
        ]);
    }
}
