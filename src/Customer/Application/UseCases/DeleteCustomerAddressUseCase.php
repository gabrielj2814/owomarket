<?php

declare(strict_types=1);

namespace Src\Customer\Application\UseCases;

use Src\Customer\Application\Contracts\Repositories\CustomerRepositoryInterface;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\Exceptions\CustomerNotFoundException;
use Src\Customer\Domain\ValueObjects\AddressId;
use Src\Customer\Domain\ValueObjects\CustomerId;

final class DeleteCustomerAddressUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository
    ) {}

    public function execute(string $customerId, string $addressId): Customer
    {
        $idVO = CustomerId::fromString($customerId);
        $customer = $this->customerRepository->findById($idVO);

        if ($customer === null) {
            throw CustomerNotFoundException::withId($idVO);
        }

        $addressIdVO = AddressId::fromString($addressId);
        $customer->removeAddress($addressIdVO);

        $this->customerRepository->save($customer);

        return $customer;
    }
}
