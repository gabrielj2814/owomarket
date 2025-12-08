<?php


namespace Src\Shared\Collection;

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

    public function toArray(): array
    {
        return [
            'data' => $this->items->toArray(),
            'meta' => [
                'total' => $this->total,
                'per_page' => $this->perPage,
                'current_page' => $this->currentPage,
                'last_page' => $this->lastPage,
            ]
        ];
    }


}

?>
