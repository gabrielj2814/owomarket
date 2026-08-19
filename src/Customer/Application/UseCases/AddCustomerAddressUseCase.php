<?php

declare(strict_types=1);

namespace Src\Customer\Application\UseCases;

use Src\Customer\Application\Contracts\Repositories\CustomerRepositoryInterface;
use Src\Customer\Application\DTOs\CustomerAddressInputData;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;
use Src\Customer\Domain\ValueObjects\CustomerId;

final class AddCustomerAddressUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(string $customerId, CustomerAddressInputData $addressData): Customer
    {
        $idVO = CustomerId::fromString($customerId);
        $customer = $this->customerRepository->findById($idVO);

        if ($customer === null) {
            throw CustomerNotFoundException::withId($idVO);
        }

        $address = $addressData->toDomainEntity();
        $customer->addAddress($address);

        $this->customerRepository->save($customer);

        return $customer;
    }
}
