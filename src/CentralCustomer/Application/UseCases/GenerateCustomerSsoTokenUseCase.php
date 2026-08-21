<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerSsoToken;
use Exception;
use Illuminate\Support\Str;

final class GenerateCustomerSsoTokenUseCase
{
    /**
     * Genera un token efímero de un solo uso con 5 minutos de validez.
     *
     * @param string $customerId
     * @param string|null $targetDomain
     * @return CentralCustomerSsoToken
     */
    public function execute(string $customerId, ?string $targetDomain = null): CentralCustomerSsoToken
    {
        $customer = CentralCustomer::find($customerId);
        if (! $customer || ! $customer->is_active) {
            throw new Exception('Cliente no encontrado o inactivo.', 404);
        }

        // Invalidate previous unused tokens for this customer and target domain
        CentralCustomerSsoToken::where('customer_id', $customerId)
            ->whereNull('used_at')
            ->delete();

        return CentralCustomerSsoToken::create([
            'id' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'token' => (string) Str::random(64),
            'target_domain' => $targetDomain ? trim($targetDomain) : null,
            'expires_at' => now()->addMinutes(5),
            'used_at' => null,
            'created_at' => now(),
        ]);
    }
}
