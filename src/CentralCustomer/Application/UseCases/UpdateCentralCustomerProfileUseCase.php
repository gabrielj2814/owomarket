<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Exception;
use Illuminate\Support\Facades\Hash;
use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;

final class UpdateCentralCustomerProfileUseCase
{
    /**
     * @param  array{name?: string, phone?: string|null, document_id?: string|null, avatar?: string|null, current_password?: string|null, new_password?: string|null}  $data
     */
    public function execute(string $customerId, array $data): CentralCustomer
    {
        $customer = CentralCustomer::with('addresses')->find($customerId);
        if (! $customer) {
            throw new Exception('Usuario no encontrado.', 404);
        }

        // Si desea cambiar contraseña, validar la contraseña actual
        if (! empty($data['new_password'])) {
            if (empty($data['current_password']) || ! Hash::check($data['current_password'], $customer->password)) {
                throw new Exception('La contraseña actual ingresada es incorrecta.', 422);
            }
            $customer->password = Hash::make($data['new_password']);
        }

        if (isset($data['name']) && trim($data['name']) !== '') {
            $customer->name = trim($data['name']);
        }

        if (array_key_exists('phone', $data)) {
            $customer->phone = $data['phone'] ? trim($data['phone']) : null;
        }

        if (array_key_exists('document_id', $data)) {
            $customer->document_id = $data['document_id'] ? trim($data['document_id']) : null;
        }

        if (array_key_exists('avatar', $data)) {
            $customer->avatar = $data['avatar'];
        }

        $customer->save();

        return $customer->fresh(['addresses']);
    }
}
