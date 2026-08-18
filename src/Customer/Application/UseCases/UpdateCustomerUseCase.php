<?php

declare(strict_types=1);

namespace Src\Customer\Application\UseCases;

use Src\Customer\Application\Contracts\Repositories\CustomerRepositoryInterface;
use Src\Customer\Application\DTOs\UpdateCustomerData;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;
use Src\Customer\Domain\Exceptions\DuplicateCustomerEmailException;
use Src\Customer\Domain\ValueObjects\CustomerEmail;
use Src\Customer\Domain\ValueObjects\CustomerId;

final class UpdateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(string $customerId, UpdateCustomerData $data): Customer
    {
        $idVO = CustomerId::fromString($customerId);
        $customer = $this->customerRepository->findById($idVO);

        if ($customer === null) {
            throw CustomerNotFoundException::withId($idVO);
        }

        $newEmailVO = CustomerEmail::fromString($data->email);
        if (! $customer->email()->equals($newEmailVO)) {
            $existing = $this->customerRepository->findByEmail($newEmailVO);
            if ($existing !== null && ! $existing->id()->equals($idVO)) {
                throw DuplicateCustomerEmailException::withEmail($newEmailVO);
            }
        }

        $customer->updateProfile(
            name: $data->name,
            email: $data->email,
            phone: $data->phone,
            birthDate: $data->birth_date,
            gender: $data->gender,
            isActive: $data->is_active,
            acceptsMarketing: $data->accepts_marketing,
            metadata: $data->metadata
        );

        $this->customerRepository->save($customer);

        return $customer;
    }
}
