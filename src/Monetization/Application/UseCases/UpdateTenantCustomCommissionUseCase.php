<?php

declare(strict_types=1);

namespace Src\Monetization\Application\UseCases;

use Exception;
use Src\Tenant\Infrastructure\Eloquent\Models\Tenant;

final class UpdateTenantCustomCommissionUseCase
{
    /**
     * @param string $tenantId
     * @param float|null $customRate Porcentaje personalizado (e.g. 2.50) o null para restablecer al plan
     * @return Tenant
     */
    public function execute(string $tenantId, ?float $customRate): Tenant
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            throw new Exception('Inquilino/Tienda no encontrado.', 404);
        }

        $data = $tenant->data ?? [];
        if ($customRate === null) {
            unset($data['custom_commission_rate']);
        } else {
            $data['custom_commission_rate'] = $customRate;
        }

        $tenant->data = $data;
        $tenant->custom_commission_rate = $customRate;
        $tenant->save();

        return $tenant;
    }
}
