<?php

declare(strict_types=1);

namespace Src\Customer\Application\Contracts\Repositories;

use Src\Customer\Application\DTOs\CustomerMetricsData;
use Src\Customer\Application\DTOs\FilterCustomersCriteria;
use Src\Customer\Application\DTOs\PaginatedCustomerResult;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\ValueObjects\CustomerEmail;
use Src\Customer\Domain\ValueObjects\CustomerId;

interface CustomerRepositoryInterface
{
    /**
     * Persiste o actualiza un cliente con sus direcciones asociadas.
     */
    public function save(Customer $customer): void;

    /**
     * Busca un cliente por su identificador UUID.
     */
    public function findById(CustomerId $id): ?Customer;

    /**
     * Busca un cliente por su correo electrónico.
     */
    public function findByEmail(CustomerEmail $email): ?Customer;

    /**
     * Filtra clientes según múltiples criterios con paginación.
     */
    public function filter(FilterCustomersCriteria $criteria): PaginatedCustomerResult;

    /**
     * Elimina un cliente lógicamente (soft delete).
     */
    public function delete(CustomerId $id): void;

    /**
     * Obtiene métricas agregadas del directorio de clientes para el dashboard.
     */
    public function getMetrics(): CustomerMetricsData;
}
