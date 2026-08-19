<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Application\DTOs\UpdateBillingProfileData;
use Src\Billing\Domain\Entities\BillingProfile;

final class UpdateBillingProfileUseCase
{
    public function __construct(
        private readonly BillingProfileRepositoryInterface $repository
    ) {}

    public function execute(UpdateBillingProfileData $data): BillingProfile
    {
        $existing = $this->repository->getProfile();

        if ($existing) {
            $existing->update(
                legalName: $data->legal_name,
                taxId: $data->tax_id,
                billingEmail: $data->billing_email,
                phone: $data->phone,
                address: $data->toAddressArray(),
                invoicePrefix: $data->invoice_prefix,
                nextInvoiceNumber: $data->next_invoice_number,
                invoiceFooterNotes: $data->invoice_footer_notes,
                logoPath: $data->logo_path,
                metadata: $data->metadata
            );
            $profile = $existing;
        } else {
            $profile = BillingProfile::create(
                legalName: $data->legal_name,
                taxId: $data->tax_id,
                billingEmail: $data->billing_email,
                phone: $data->phone,
                address: $data->toAddressArray(),
                invoicePrefix: $data->invoice_prefix,
                nextInvoiceNumber: $data->next_invoice_number,
                invoiceFooterNotes: $data->invoice_footer_notes,
                logoPath: $data->logo_path,
                metadata: $data->metadata
            );
        }

        return $this->repository->save($profile);
    }
}
