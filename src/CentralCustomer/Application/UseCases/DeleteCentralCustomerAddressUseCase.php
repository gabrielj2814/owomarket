<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Exception;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomerAddress;

final class DeleteCentralCustomerAddressUseCase
{
    /**
     * @return array{success: bool, message: string}
     */
    public function execute(string $customerId, string $addressId): array
    {
        $address = CentralCustomerAddress::where('id', $addressId)
            ->where('customer_id', $customerId)
            ->first();

        if (! $address) {
            throw new Exception('Dirección no encontrada.', 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // Si era la predeterminada y quedan otras, marcar la primera como predeterminada
        if ($wasDefault) {
            $next = CentralCustomerAddress::where('customer_id', $customerId)->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return [
            'success' => true,
            'message' => 'Dirección eliminada correctamente.',
        ];
    }
}
