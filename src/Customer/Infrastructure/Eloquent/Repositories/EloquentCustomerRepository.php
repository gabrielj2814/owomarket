<?php

declare(strict_types=1);

namespace Src\Customer\Infrastructure\Eloquent\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Src\Customer\Application\Contracts\Repositories\CustomerRepositoryInterface;
use Src\Customer\Application\DTOs\CustomerMetricsData;
use Src\Customer\Application\DTOs\FilterCustomersCriteria;
use Src\Customer\Application\DTOs\PaginatedCustomerResult;
use Src\Customer\Domain\Entities\Customer;
use Src\Customer\Domain\Entities\CustomerAddress;
use Src\Customer\Domain\ValueObjects\AddressId;
use Src\Customer\Domain\ValueObjects\AddressType;
use Src\Customer\Domain\ValueObjects\BirthDate;
use Src\Customer\Domain\ValueObjects\CustomerEmail;
use Src\Customer\Domain\ValueObjects\CustomerId;
use Src\Customer\Domain\ValueObjects\CustomerName;
use Src\Customer\Domain\ValueObjects\CustomerPhone;
use Src\Customer\Domain\ValueObjects\Gender;
use Src\Customer\Infrastructure\Eloquent\Models\Address as EloquentAddress;
use Src\Customer\Infrastructure\Eloquent\Models\Customer as EloquentCustomer;

final class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function save(Customer $customer): void
    {
        DB::transaction(function () use ($customer) {
            /** @var EloquentCustomer $eloquentCustomer */
            $eloquentCustomer = EloquentCustomer::query()->updateOrCreate(
                ['id' => $customer->id()->value()],
                [
                    'name' => $customer->name()->value(),
                    'email' => $customer->email()->value(),
                    'phone' => $customer->phone()?->value(),
                    'birth_date' => $customer->birthDate()?->value(),
                    'gender' => $customer->gender()?->value(),
                    'is_active' => $customer->isActive(),
                    'accepts_marketing' => $customer->acceptsMarketing(),
                    'metadata' => $customer->metadata(),
                ]
            );

            // Sincronizar direcciones
            $domainAddressIds = [];
            foreach ($customer->addresses() as $address) {
                $domainAddressIds[] = $address->id()->value();

                EloquentAddress::query()->updateOrCreate(
                    [
                        'id' => $address->id()->value(),
                        'addressable_type' => EloquentCustomer::class,
                        'addressable_id' => $customer->id()->value(),
                    ],
                    [
                        'type' => $address->type()->value(),
                        'first_name' => $address->firstName(),
                        'last_name' => $address->lastName(),
                        'company' => $address->company(),
                        'address_line_1' => $address->addressLine1(),
                        'address_line_2' => $address->addressLine2(),
                        'city' => $address->city(),
                        'state' => $address->state(),
                        'postal_code' => $address->postalCode(),
                        'country' => $address->country(),
                        'phone' => $address->phone(),
                        'is_default' => $address->isDefault(),
                    ]
                );
            }

            // Eliminar direcciones eliminadas del aggregate
            EloquentAddress::query()
                ->where('addressable_type', EloquentCustomer::class)
                ->where('addressable_id', $customer->id()->value())
                ->whereNotIn('id', $domainAddressIds)
                ->delete();
        });
    }

    public function findById(CustomerId $id): ?Customer
    {
        /** @var EloquentCustomer|null $eloquentCustomer */
        $eloquentCustomer = EloquentCustomer::query()
            ->with('addresses')
            ->find($id->value());

        if ($eloquentCustomer === null) {
            return null;
        }

        return $this->toDomainEntity($eloquentCustomer);
    }

    public function findByEmail(CustomerEmail $email): ?Customer
    {
        /** @var EloquentCustomer|null $eloquentCustomer */
        $eloquentCustomer = EloquentCustomer::query()
            ->with('addresses')
            ->where('email', $email->value())
            ->first();

        if ($eloquentCustomer === null) {
            return null;
        }

        return $this->toDomainEntity($eloquentCustomer);
    }

    public function filter(FilterCustomersCriteria $criteria): PaginatedCustomerResult
    {
        $query = EloquentCustomer::query()->with('addresses');

        if ($criteria->search !== null && trim($criteria->search) !== '') {
            $searchTerm = '%'.trim($criteria->search).'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', $searchTerm)
                    ->orWhere('email', 'LIKE', $searchTerm)
                    ->orWhere('phone', 'LIKE', $searchTerm);
            });
        }

        if ($criteria->is_active !== null) {
            $query->where('is_active', $criteria->is_active);
        }

        if ($criteria->accepts_marketing !== null) {
            $query->where('accepts_marketing', $criteria->accepts_marketing);
        }

        if ($criteria->gender !== null && trim($criteria->gender) !== '') {
            $query->where('gender', $criteria->gender);
        }

        $sortField = in_array($criteria->sort_by, ['name', 'email', 'created_at'], true)
            ? $criteria->sort_by
            : 'created_at';

        $sortDirection = strtolower($criteria->sort_direction) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDirection);

        $paginator = $query->paginate(
            perPage: $criteria->per_page,
            page: $criteria->page
        );

        $items = [];
        foreach ($paginator->items() as $item) {
            $items[] = $this->toDomainEntity($item);
        }

        return new PaginatedCustomerResult(
            items: $items,
            total: $paginator->total(),
            currentPage: $paginator->currentPage(),
            perPage: $paginator->perPage(),
            lastPage: $paginator->lastPage()
        );
    }

    public function delete(CustomerId $id): void
    {
        EloquentCustomer::query()->where('id', $id->value())->delete();
    }

    public function getMetrics(): CustomerMetricsData
    {
        $total = EloquentCustomer::query()->count();
        $active = EloquentCustomer::query()->where('is_active', true)->count();
        $marketing = EloquentCustomer::query()->where('accepts_marketing', true)->count();
        $newThisMonth = EloquentCustomer::query()
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        return new CustomerMetricsData(
            total_customers: $total,
            active_customers: $active,
            marketing_subscribers: $marketing,
            new_this_month: $newThisMonth
        );
    }

    private function toDomainEntity(EloquentCustomer $model): Customer
    {
        $addresses = [];
        if ($model->relationLoaded('addresses') && $model->addresses !== null) {
            foreach ($model->addresses as $addrModel) {
                $addresses[] = new CustomerAddress(
                    id: AddressId::fromString((string) $addrModel->id),
                    type: AddressType::fromString((string) $addrModel->type),
                    firstName: (string) $addrModel->first_name,
                    lastName: (string) $addrModel->last_name,
                    addressLine1: (string) $addrModel->address_line_1,
                    city: (string) $addrModel->city,
                    state: (string) $addrModel->state,
                    postalCode: (string) $addrModel->postal_code,
                    country: (string) $addrModel->country,
                    addressLine2: $addrModel->address_line_2,
                    company: $addrModel->company,
                    phone: $addrModel->phone,
                    isDefault: (bool) $addrModel->is_default,
                    createdAt: $addrModel->created_at?->toISOString(),
                    updatedAt: $addrModel->updated_at?->toISOString()
                );
            }
        }

        return new Customer(
            id: CustomerId::fromString((string) $model->id),
            name: CustomerName::fromString((string) $model->name),
            email: CustomerEmail::fromString((string) $model->email),
            phone: CustomerPhone::nullable($model->phone),
            birthDate: BirthDate::nullable($model->birth_date?->format('Y-m-d')),
            gender: Gender::nullable($model->gender),
            isActive: (bool) $model->is_active,
            acceptsMarketing: (bool) $model->accepts_marketing,
            metadata: $model->metadata,
            addresses: $addresses,
            createdAt: $model->created_at?->toISOString(),
            updatedAt: $model->updated_at?->toISOString()
        );
    }
}
