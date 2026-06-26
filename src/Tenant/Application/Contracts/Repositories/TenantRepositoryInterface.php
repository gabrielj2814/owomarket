<?php


namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Shared\Collection\Pagination;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValuesObjects\Slug;
use Src\Tenant\Domain\ValuesObjects\Uuid;

interface TenantRepositoryInterface {


    /**
     * Método filter.
     */


    public function filter(
        string | null $search,
        string | null $fechaDesdeUTC,
        string | null $fechaHastaUTC,
        string | null $status,
        string | null $request,
        int $prePage=50
    ): Pagination;

    /**
     * Método consultTenantById.
     */

    public function consultTenantById(Uuid $uuid):? Tenant;

    /**
     * Método consultTenantsByIdOwnerPaginate.
     */

    public function consultTenantsByIdOwnerPaginate(Uuid $uuid, int $prePage=50): Pagination;

    /**
     * Método consultTenantBySlug.
     */

    public function consultTenantBySlug(Slug $slug):? Tenant;

    /**
     * Método suspended.
     */

    public function suspended(Tenant $tenant): Tenant;

    /**
     * Método inactive.
     */

    public function inactive(Tenant $tenant): Tenant;

    /**
     * Método active.
     */

    public function active(Tenant $tenant): Tenant;

    /**
     * Método save.
     */

    public function save(Tenant $tenant): Tenant;

    /**
     * Método deleteTenant.
     */

    public function deleteTenant(Uuid $id): bool;

    /**
     * Método deleteForceTenant.
     */

    public function deleteForceTenant(Uuid $id): bool;

    /**
     * Método changedRequestStatus.
     */

    public function changedRequestStatus(Tenant $tenant): Tenant;




}



?>
