<?php

declare(strict_types=1);

namespace Src\Billing\Application\Contracts\Repositories;

use Src\Billing\Domain\Entities\BillingProfile;

interface BillingProfileRepositoryInterface
{
    /**
     * Obtiene el perfil fiscal actual del tenant (o null si aún no se ha creado).
     */
    public function getProfile(): ?BillingProfile;

    /**
     * Guarda o actualiza el perfil fiscal del tenant.
     */
    public function save(BillingProfile $profile): BillingProfile;
}
