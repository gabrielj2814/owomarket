<?php

declare(strict_types=1);

namespace Src\Billing\Application\Contracts\Repositories;

use Src\Billing\Application\DTOs\FilterInvoicesCriteria;
use Src\Billing\Application\DTOs\PaginatedInvoicesResult;
use Src\Billing\Domain\Entities\Invoice;
use Src\Billing\Domain\ValueObjects\InvoiceId;
use Src\Billing\Domain\ValueObjects\InvoiceNumber;

interface InvoiceRepositoryInterface
{
    /**
     * Guarda una factura (incluyendo sus ítems).
     */
    public function save(Invoice $invoice): Invoice;

    /**
     * Busca una factura por su ID único.
     */
    public function findById(InvoiceId $id): ?Invoice;

    /**
     * Busca una factura por su número correlativo.
     */
    public function findByNumber(InvoiceNumber $number): ?Invoice;

    /**
     * Actualiza el estado o datos de una factura existente.
     */
    public function update(Invoice $invoice): Invoice;

    /**
     * Filtra facturas con paginación y criterios avanzados.
     */
    public function filter(FilterInvoicesCriteria $criteria): PaginatedInvoicesResult;

    /**
     * Obtiene métricas agregadas (total facturado, cantidad emitida, pagada, cancelada).
     *
     * @return array{total_billed: float, total_issued: int, total_paid: int, total_cancelled: int}
     */
    public function getMetrics(): array;
}
