<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListAdminClaimsUseCase;

/**
 * La mesa de reclamaciones de la plataforma (subsistema 5, fase D).
 *
 * Hasta que existió, `BuildClaimDossierUseCase` era código inalcanzable: pedía un `claimId` y
 * ninguna pantalla central listaba reclamaciones, así que no había forma de tener uno.
 */
final class ViewAdminClaimsPageGETController
{
    public function __construct(
        private readonly ListAdminClaimsUseCase $useCase
    ) {}

    public function index(Request $request, string $user_uuid): Response
    {
        $filtros = [
            // Por defecto las abiertas, que es el mismo filtro con el que arranca el selector
            // de la pantalla. Si aquí no se aplicara, la primera carga mostraría todas bajo
            // una etiqueta que dice «Abiertas».
            'status' => $request->query('status', 'open'),
            'search' => $request->query('search'),
            'page' => (int) $request->query('page', 1),
        ];

        $resultado = $this->useCase->execute($filtros);

        return Inertia::render('admin/support/AdminClaimDossierPage', [
            'title' => 'Reclamaciones y Expedientes - OwOMarket Admin',
            'user_id' => $user_uuid,
            'claims' => $resultado['claims'],
            'pagination' => $resultado['pagination'],
            'metrics' => $resultado['metrics'],
            'filters' => $filtros,
        ]);
    }
}
