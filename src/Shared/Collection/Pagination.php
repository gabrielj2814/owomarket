<?php

namespace Src\Shared\Collection;

/**
 * Colección paginada del dominio.
 *
 * Hallazgo N37: tenía un `toArray()` que devolvía `{ data, meta }` —una séptima forma de
 * paginación, distinta de las seis que había en el cable— y los tres controladores que lo
 * usaban tenían que desmontarla a mano para responder. Se sustituye por getters: quien
 * decide el formato de la respuesta es `ApiResponse::paginated()`, y esta clase sólo
 * transporta los datos.
 */
class Pagination
{
    public function __construct(
        private Collection $items,
        private int $total,
        private int $perPage,
        private int $currentPage,
        private int $lastPage
    ) {}

    /**
     * @return Collection<T>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getLastPage(): int
    {
        return $this->lastPage;
    }
}
