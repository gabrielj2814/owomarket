<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Src\CentralCustomer\Infrastructure\Eloquent\Models\CentralCustomer;
use Exception;

final class ToggleCentralCustomerStatusUseCase
{
    public function execute(string $customerId, ?string $reason = null): CentralCustomer
    {
        $customer = CentralCustomer::find($customerId);

        if (! $customer) {
            throw new Exception("Cliente '{$customerId}' no encontrado.", 404);
        }

        $customer->is_active = ! $customer->is_active;

        if ($reason) {
            $notes = $customer->notes ?? '';
            $statusText = $customer->is_active ? 'Reactivado' : 'Bloqueado';
            $customer->notes = trim("{$notes}\n[" . now()->toIso8601String() . "] {$statusText}: {$reason}");
        }

        $customer->save();

        return $customer;
    }
}
