<?php

declare(strict_types=1);

namespace Src\Billing\Application\Contracts\Repositories;

use Src\Billing\Domain\Entities\BillingProfile;

interface BillingProfileRepositoryInterface
{
    /**
     * Obtiene el perfil fiscal actual del tenant (o null si aún no se ha creado).
     */
    public function getProfile(): ?BillingProfile;

    /**
     * Guarda o actualiza el perfil fiscal del tenant.
     */
    public function save(BillingProfile $profile): BillingProfile;

    /**
     * Reserva de forma ATÓMICA el siguiente número correlativo de factura.
     *
     * Hallazgo C4: el flujo anterior leía el perfil sin bloqueo, incrementaba
     * el contador en memoria y lo persistía aparte, así que dos operadores
     * emitiendo factura a la vez con `next_invoice_number = 42` generaban
     * ambos `FAC-2026-000042`.
     *
     * La implementación debe bloquear la fila del perfil (`lockForUpdate`)
     * dentro de una transacción, de modo que el número quede consumido antes
     * de que otra petición pueda leerlo.
     *
     * @return string Número formateado (ej: FAC-2026-000042)
     */
    public function reserveNextInvoiceNumber(): string;
}
