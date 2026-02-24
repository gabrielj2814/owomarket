<?php


namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\ValuesObjects\Slug;

class ConsultTenantLoginIsActiveUseCase {

    public function __construct(
        protected TenantRepositoryInterface $tenantRepository
    ) {}


    public function execute(string $slug, string $domain): bool{
        $VOslug = Slug::fromString($slug, $domain);
        $tenant = $this->tenantRepository->consultTenantBySlug($VOslug);
        if($tenant === null){
            throw new Exception("Tenant no encontrado para el slug: {$VOslug->value()}",404);
        }

        return $tenant->loginIsActive();
    }




}



?>
