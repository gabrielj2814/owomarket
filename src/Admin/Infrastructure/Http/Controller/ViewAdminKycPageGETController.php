<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Admin\Application\UseCase\ListTenantKycProfilesUseCase;

/**
 * La pantalla de verificación de identidad (subsistema 1).
 *
 * Hasta que existió, `ReviewTenantKycUseCase` no tenía ni controlador ni ruta: el KYC exigía
 * verificación para retirar y **no había ninguna forma de verificar a nadie**, así que todos
 * los expedientes se quedaban en `pending` y ninguna tienda podía cobrar jamás.
 */
final class ViewAdminKycPageGETController
{
    public function __construct(
        private readonly ListTenantKycProfilesUseCase $useCase
    ) {}

    public function index(Request $request, string $user_uuid): Response
    {
        $filtros = [
            // Por defecto, lo pendiente. La pantalla arranca con ese filtro seleccionado, y si
            // aquí no se aplicara el mismo, la primera carga mostraría TODOS los expedientes
            // bajo una etiqueta que dice «Pendientes»: el selector mentiría hasta que alguien
            // lo tocara.
            'status' => $request->query('status', 'pending'),
            'search' => $request->query('search'),
            'page' => (int) $request->query('page', 1),
        ];

        $resultado = $this->useCase->execute($filtros);

        return Inertia::render('admin/kyc/AdminKycReviewPage', [
            'title' => 'Verificación de Identidad de Comerciantes - OwOMarket Admin',
            'user_id' => $user_uuid,
            'profiles' => $resultado['profiles'],
            'pagination' => $resultado['pagination'],
            'metrics' => $resultado['metrics'],
            'filters' => $filtros,
        ]);
    }
}
