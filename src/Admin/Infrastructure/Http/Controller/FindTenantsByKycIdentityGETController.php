<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Src\Admin\Application\UseCase\FindTenantsByIdentityUseCase;
use Illuminate\Http\JsonResponse;
use Src\Shared\Helper\ApiResponse;
use Src\Tenant\Infrastructure\Eloquent\Models\TenantKycProfile;

/**
 * Qué otras tiendas hay detrás de la identidad de ESTE expediente (subsistema 1).
 *
 * ## Por qué se busca por expediente y no por un número que el administrador escriba
 *
 * `PLAN_VISTAS_PENDIENTES.md` proponía `GET .../kyc/identity-search` con la cédula o el RIF en
 * la consulta. No se hace así, y conviene dejar escrito el porqué antes de que alguien lo
 * "arregle":
 *
 * **La pantalla no tiene el número.** Está cifrado y el listado no lo devuelve --a propósito--,
 * así que un buscador de texto libre en el backoffice sería una caja que nadie puede rellenar.
 * Peor: obligaría a que el número viajara al navegador para poder escribirlo, deshaciendo por
 * la puerta de atrás el cifrado que el subsistema acaba de poner.
 *
 * Aquí el número nunca sale del servidor: se descifra, se convierte en hash y se compara. Lo
 * que vuelve al navegador son nombres de tienda, no documentos.
 *
 * ## Esto informa, no bloquea
 *
 * Tener dos tiendas con la misma cédula **no es una falta** --un dueño con dos negocios es
 * normal--. La respuesta le da contexto a quien decide; que la pantalla lo diga con palabras es
 * parte del trabajo, porque un listado sin explicación se lee como una acusación.
 */
final class FindTenantsByKycIdentityGETController
{
    public function __construct(
        private readonly FindTenantsByIdentityUseCase $useCase
    ) {}

    public function __invoke(string $profileId): JsonResponse
    {
        $perfil = TenantKycProfile::find($profileId);

        if ($perfil === null) {
            return ApiResponse::error('Expediente de verificación no encontrado.', 404);
        }

        $coincidencias = collect($this->useCase->execute($perfil->cedula, $perfil->rif))
            // El propio expediente siempre coincide consigo mismo. Devolverlo obligaría a cada
            // pantalla a acordarse de filtrarlo, y la que se olvide dirá que el comerciante
            // tiene una tienda de más.
            ->reject(fn (array $otro) => $otro['tenant_id'] === $perfil->tenant_id)
            ->values()
            ->all();

        return ApiResponse::success(
            data: $coincidencias,
            message: $coincidencias === []
                ? 'Esta identidad no figura en ninguna otra tienda.'
                : 'Esta identidad figura en otras tiendas.'
        );
    }
}
