<?php

namespace Src\Tenant\Application\UseCase;

use Exception;
use Src\Shared\Domain\Contracts\UuidGenerator;
use Src\Shared\Domain\ValueObjects\Currency;
use Src\Shared\Domain\ValueObjects\Timezone;
use Src\Tenant\Application\Contracts\Repositories\TenantRepositoryInterface;
use Src\Tenant\Domain\Entities\Tenant;
use Src\Tenant\Domain\ValueObjects\Slug;
use Src\Tenant\Domain\ValueObjects\TenantName;
use Src\Tenant\Domain\ValueObjects\TenantRequest;
use Src\Tenant\Domain\ValueObjects\TenantStatus;

class CreateTenantUseCase
{
    /**
     * Constructor de la clase.
     */
    public function __construct(
        protected TenantRepositoryInterface $tenantRepository,
        protected UuidGenerator $generator,
    ) {}

    /**
     * Método execute.
     */
    public function execute(string $name, string $domain): Tenant
    {

        $name = TenantName::make($name);
        $slug = Slug::make($name->value(), $domain);
        $status = TenantStatus::active();
        $request = TenantRequest::inProgress();
        $timezone = Timezone::make('UTC');
        $currency = Currency::make('USD');

        $tenantConSlugEnUso = $this->tenantRepository->consultTenantBySlug($slug);

        if ($tenantConSlugEnUso !== null) {
            throw new Exception('Slug already in use', 400);
        }

        $tenant = Tenant::create(
            $this->generator,
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
