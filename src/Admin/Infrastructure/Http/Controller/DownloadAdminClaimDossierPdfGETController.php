<?php

declare(strict_types=1);

namespace Src\Admin\Infrastructure\Http\Controller;

use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Response;
use Src\Admin\Application\UseCase\BuildClaimDossierUseCase;

/**
 * El expediente en un papel que se pueda entregar (subsistema 5, fase D).
 *
 * El destino de este documento es acompañar una denuncia, así que tiene que salir de la
 * pantalla en algo entregable. Se genera en el servidor con dompdf --el mismo camino que las
 * facturas-- y no con la impresión del navegador: un PDF del servidor sale idéntico para todos
 * y sin las cabeceras ni las URLs que el navegador estampa en el papel.
 *
 * **Este PDF lleva la identidad completa del comerciante.** No es un descuido: lo decide el
 * bloque `store` de `BuildClaimDossierUseCase`, que tiene su nota `pendiente-abogado:`. Esta
 * ruta no filtra nada por su cuenta, porque dos sitios decidiendo qué datos salen es como
 * acaban divergiendo.
 */
final class DownloadAdminClaimDossierPdfGETController
{
    public function __construct(
        private readonly BuildClaimDossierUseCase $useCase
    ) {}

    public function __invoke(string $claimId): Response
    {
        try {
            $expediente = $this->useCase->execute($claimId);
        } catch (Exception $e) {
            abort((int) $e->getCode() === 404 ? 404 : 400, $e->getMessage());
        }

        $pdf = Pdf::loadView('claims.dossier-pdf', ['expediente' => $expediente]);
        $pdf->setPaper('a4', 'portrait');

        $numero = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $expediente['claim']['order_number']) ?: 'reclamacion';

        return $pdf->download("expediente-{$numero}.pdf");
    }
}
