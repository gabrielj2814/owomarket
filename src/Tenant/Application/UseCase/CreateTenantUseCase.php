<?php


namespace Src\Tenant\Application\UseCase;

use Src\Shared\ValuesObjects\Currency;
use Src\Shared\ValuesObjects\Timezone;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValuesObjects\Slug;
use Src\Tenant\Domain\ValuesObjects\TenantName;
use Src\Tenant\Domain\ValuesObjects\TenantRequest;
use Src\Tenant\Domain\ValuesObjects\TenantStatus;

class CreateTenantUseCase {


    public function __construct(
        protected TenantRepositoryInterface $tenantRepository,
    ) {}

    public function execute(string $name){

        $name= TenantName::make($name);
        $slug= Slug::fromString($name->value());
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

        return $this->tenantRepository->save($tenant);
    }




}


?>
