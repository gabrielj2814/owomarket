<?php


namespace Src\Tenant\Application\UseCase;

use Exception;
use Illuminate\Support\Facades\Log;
use Src\Shared\Domain\ValueObjects\Currency;
use Src\Shared\Domain\ValueObjects\Timezone;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValueObjects\Slug;
use Src\Tenant\Domain\ValueObjects\TenantName;
use Src\Tenant\Domain\ValueObjects\TenantRequest;
use Src\Tenant\Domain\ValueObjects\TenantStatus;

class CreateTenantUseCase {


    /**
     * Constructor de la clase.
     */


    public function __construct(
        protected TenantRepositoryInterface $tenantRepository,
    ) {}

    /**
     * Método execute.
     */

    public function execute(string $name, string $domain): Tenant{

        $name= TenantName::make($name);
        $slug= Slug::make($name->value(), $domain);
        $status= TenantStatus::active();
        $request= TenantRequest::inProgress();
        $timezone= Timezone::make('UTC');
        $currency= Currency::make('USD');
        $tenant = Tenant::create(
            $name,
            $slug,
            $status,
            $timezone,
            $currency,
            $request,
        );

        $tenantConSlugEnUso= $this->tenantRepository->consultTenantBySlug($slug);

        if($tenantConSlugEnUso !== null){
            throw new Exception("Slug already in use", 400);
        }

        return $this->tenantRepository->save($tenant);
    }




}


?>
