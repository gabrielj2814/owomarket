<?php


namespace Src\Tenant\Application\Contracts\Repositories;

use Src\Shared\Collection\Pagination;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValuesObjects\Slug;
use Src\Tenant\Domain\ValuesObjects\Uuid;

interface TenantRepositoryInterface {


    public function filter(
        string | null $search,
        string | null $fechaDesdeUTC,
        string | null $fechaHastaUTC,
        string | null $status,
        string | null $request,
        int $prePage=50
    ): Pagination;

    public function consultTenantById(Uuid $uuid):? Tenant;

    public function consultTenantBySlug(Slug $slug):? Tenant;

    public function suspended(Tenant $tenant): Tenant;

    public function inactive(Tenant $tenant): Tenant;

    public function active(Tenant $tenant): Tenant;

    public function save(Tenant $tenant): Tenant;

    public function deleteTenant(Uuid $id): bool;

    public function deleteForceTenant(Uuid $id): bool;

    public function changedRequestStatus(Tenant $tenant): Tenant;




}



?>
