<?php

declare(strict_types=1);

namespace Src\Shipment\Application\DTOs;

use Src\Shipment\Domain\Entities\Shipment;

final class PaginatedShipmentResult
{
    /**
     * @param  Shipment[]  $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
        public readonly int $lastPage
    ) {}

    /**
     * Sólo los elementos. Los contadores ya son propiedades públicas de este DTO, así que
     * duplicarlos aquí dentro era lo que producía un sobre de paginación distinto por
     * módulo (hallazgo N37). Quien responde es `ApiResponse::paginated()`, que es el único
     * sitio donde vive el formato.
     */
    public function itemsToArray(): array
    {
        return array_map(fn (Shipment $s) => $s->toArray(), $this->items);
    }
}
