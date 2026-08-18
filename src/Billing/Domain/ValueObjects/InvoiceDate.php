<?php

declare(strict_types=1);

namespace Src\Billing\Domain\ValueObjects;

use DateTimeImmutable;
use InvalidArgumentException;

final class InvoiceDate
{
    private function __construct(
        private readonly DateTimeImmutable $issueDate,
        private readonly ?DateTimeImmutable $dueDate = null
    ) {
        if ($this->dueDate !== null && $this->dueDate < $this->issueDate) {
            throw new InvalidArgumentException('La fecha de vencimiento no puede ser anterior a la fecha de emisión.');
        }
    }

    public static function create(?string $issueDate = null, ?string $dueDate = null): self
    {
        $issue = $issueDate ? new DateTimeImmutable($issueDate) : new DateTimeImmutable('today');
        $due = $dueDate ? new DateTimeImmutable($dueDate) : null;

        return new self($issue, $due);
    }

    public function issueDate(): DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function dueDate(): ?DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function issueDateFormatted(string $format = 'Y-m-d'): string
    {
        return $this->issueDate->format($format);
    }

    public function dueDateFormatted(string $format = 'Y-m-d'): ?string
    {
        return $this->dueDate?->format($format);
    }
}
