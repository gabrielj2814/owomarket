<?php

declare(strict_types=1);

namespace Src\CentralCustomer\Application\UseCases;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class RegisterCentralCustomerUseCase
{
    /**
     * @param array{name: string, email: string, password: string, phone?: string|null, document_id?: string|null} $data
     * @return array{customer: CentralCustomer, token: string}
     */
    public function execute(array $data): array
    {
        $existing = CentralCustomer::where('email', strtolower(trim($data['email'])))->first();
        if ($existing) {
            throw new Exception('Ya existe una cuenta registrada con este correo electrónico.', 422);
        }

        $customer = CentralCustomer::create([
            'id' => (string) Str::uuid(),
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
            'document_id' => isset($data['document_id']) ? trim($data['document_id']) : null,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        return [
            'customer' => $customer,
            'token' => (string) Str::random(64),
        ];
    }
}
