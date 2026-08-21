<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerSsoToken;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as TenantCustomer;

final class ValidateAndConsumeSsoTokenUseCase
{
    /**
     * @param string $token
     * @param string|null $currentDomain
     * @return array{customer: TenantCustomer, central_customer: array<string, mixed>, addresses: array<int, mixed>}
     */
    public function execute(string $token, ?string $currentDomain = null): array
    {
        $ssoToken = CentralCustomerSsoToken::with('customer.addresses')
            ->where('token', trim($token))
            ->first();

        if (! $ssoToken) {
            throw new Exception('Token de inicio de sesión no encontrado o inválido.', 404);
        }

        if ($ssoToken->used_at !== null) {
            throw new Exception('Este enlace de inicio de sesión ya fue utilizado.', 410);
        }

        if ($ssoToken->expires_at->isPast()) {
            throw new Exception('El token de inicio de sesión ha expirado.', 401);
        }

        // Mark as used
        $ssoToken->update(['used_at' => now()]);

        $centralCustomer = $ssoToken->customer;
        if (! $centralCustomer || ! $centralCustomer->is_active) {
            throw new Exception('La cuenta del usuario central se encuentra inactiva.', 403);
        }

        // Sincronizar o crear en la base de datos del tenant actual
        $tenantCustomer = TenantCustomer::where('central_uuid', $centralCustomer->id)->first()
            ?? TenantCustomer::where('email', $centralCustomer->email)->first();

        if ($tenantCustomer) {
            $tenantCustomer->update([
                'central_uuid' => $centralCustomer->id,
                'name' => $centralCustomer->name,
                'email' => $centralCustomer->email,
                'phone' => $centralCustomer->phone ?? $tenantCustomer->phone,
                'is_active' => true,
            ]);
        } else {
            $tenantCustomer = TenantCustomer::create([
                'id' => (string) Str::uuid(),
                'central_uuid' => $centralCustomer->id,
                'name' => $centralCustomer->name,
                'email' => $centralCustomer->email,
                'phone' => $centralCustomer->phone,
                'is_active' => true,
                'accepts_marketing' => false,
            ]);
        }

        return [
            'customer' => $tenantCustomer,
            'central_customer' => [
                'id' => $centralCustomer->id,
                'name' => $centralCustomer->name,
                'email' => $centralCustomer->email,
                'phone' => $centralCustomer->phone,
                'document_id' => $centralCustomer->document_id,
                'avatar' => $centralCustomer->avatar,
            ],
            'addresses' => $centralCustomer->addresses->toArray(),
        ];
    }
}
