<?php

declare(strict_types=1);

namespace Src\Billing\Application\UseCases;

use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Application\Contracts\Repositories\InvoiceRepositoryInterface;
use Src\Billing\Application\DTOs\CreateDirectInvoiceData;
use Src\Billing\Application\DTOs\InvoiceItemData;
use Src\Billing\Domain\Entities\BillingProfile;
use Src\Billing\Domain\Entities\Invoice;

final class CreateDirectInvoiceUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly BillingProfileRepositoryInterface $billingProfileRepository
    ) {}

    public function execute(CreateDirectInvoiceData $data): Invoice
    {
        // 1. Obtener o crear perfil fiscal por defecto
        $profile = $this->billingProfileRepository->getProfile();
        if (! $profile) {
            $profile = BillingProfile::create(
                legalName: 'Mi Empresa',
                taxId: 'CL-11223344-5',
                billingEmail: 'facturacion@store.com',
                phone: null,
                address: [
                    'address_line_1' => 'Dirección Comercial 123',
                    'city' => 'Santiago',
                    'state' => 'RM',
                    'postal_code' => '8320000',
                    'country' => 'Chile',
                ],
                invoicePrefix: 'FAC-',
                nextInvoiceNumber: 1
            );
            $this->billingProfileRepository->save($profile);
        }

        // 2. Generar e incrementar número de factura correlativo
        $invoiceNumber = $profile->generateAndIncrementInvoiceNumber();
        $this->billingProfileRepository->save($profile);

        // 3. Mapear ítems a entidades de dominio
        $items = array_map(
            fn (InvoiceItemData $itemData) => $itemData->toDomain(),
            $data->items
        );

        // 4. Crear agregado Invoice
        $invoice = Invoice::createDirect(
            invoiceNumber: $invoiceNumber,
            customer: [
                'name' => $data->customer_name,
                'tax_id' => $data->customer_tax_id,
                'email' => $data->customer_email,
                'address' => $data->toCustomerAddressArray(),
            ],
            issuer: $profile->toArray(),
            items: $items,
            paymentMethod: $data->payment_method,
            paymentStatus: $data->payment_status,
            status: $data->status,
            issueDate: $data->issue_date,
            dueDate: $data->due_date,
            currency: $data->currency,
            notes: $data->notes,
            orderId: $data->order_id,
            customerId: $data->customer_id,
            metadata: $data->metadata
        );

        // 5. Persistir factura
        return $this->invoiceRepository->save($invoice);
    }
}
