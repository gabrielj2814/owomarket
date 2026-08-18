<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Domain\Entities\BillingProfile;

final class ConsultBillingProfileUseCase
{
    public function __construct(
        private readonly BillingProfileRepositoryInterface $repository
    ) {}

    public function execute(): ?BillingProfile
    {
        return $this->repository->getProfile();
    }
}
