<?php

declare(strict_types=1);

namespace Src\Customer\Application\UseCases;

use Src\Customer\Application\Contracts\Repositories\CustomerRepositoryInterface;
use Src\Customer\Application\DTOs\CreateCustomerData;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\Exceptions\DuplicateCustomerEmailException;
use Src\Customer\Domain\ValueObjects\CustomerEmail;

final class CreateCustomerUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(CreateCustomerData $data): Customer
    {
        $emailVO = CustomerEmail::fromString($data->email);

        $existing = $this->customerRepository->findByEmail($emailVO);
        if ($existing !== null) {
            throw DuplicateCustomerEmailException::withEmail($emailVO);
        }

        $addresses = [];
        foreach ($data->addresses as $addrDto) {
            $addresses[] = $addrDto->toDomainEntity();
        }

        $customer = Customer::create(
            name: $data->name,
            email: $data->email,
            phone: $data->phone,
            birthDate: $data->birth_date,
            gender: $data->gender,
            isActive: $data->is_active,
            acceptsMarketing: $data->accepts_marketing,
            metadata: $data->metadata,
            addresses: $addresses
        );

        $this->customerRepository->save($customer);

        return $customer;
    }
}
