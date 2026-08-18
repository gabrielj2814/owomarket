<?php

declare(strict_types=1);

namespace Src\Order\Application\DTOs;

final class FilterOrdersCriteria
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $customerId = null,
        public readonly ?string $status = null,
        public readonly ?string $paymentStatus = null,
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly int $perPage = 15,
        public readonly int $page = 1,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDirection = 'desc'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            search: isset($data['search']) && trim((string) $data['search']) !== '' ? trim((string) $data['search']) : null,
            customerId: isset($data['customer_id']) && trim((string) $data['customer_id']) !== '' ? trim((string) $data['customer_id']) : null,
            status: isset($data['status']) && trim((string) $data['status']) !== '' ? trim((string) $data['status']) : null,
            paymentStatus: isset($data['payment_status']) && trim((string) $data['payment_status']) !== '' ? trim((string) $data['payment_status']) : null,
            startDate: isset($data['start_date']) && trim((string) $data['start_date']) !== '' ? trim((string) $data['start_date']) : null,
            endDate: isset($data['end_date']) && trim((string) $data['end_date']) !== '' ? trim((string) $data['end_date']) : null,
            perPage: isset($data['per_page']) ? max(1, (int) $data['per_page']) : 15,
            page: isset($data['page']) ? max(1, (int) $data['page']) : 1,
            sortBy: (string) ($data['sort_by'] ?? 'created_at'),
            sortDirection: strtolower((string) ($data['sort_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc'
        );
    }
}
