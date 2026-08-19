<?php

declare(strict_types=1);

use Src\Billing\Application\Contracts\Repositories\BillingProfileRepositoryInterface;
use Src\Billing\Application\DTOs\UpdateBillingProfileData;
use Src\Billing\Application\UseCases\ConsultBillingProfileUseCase;
use Src\Billing\Application\UseCases\UpdateBillingProfileUseCase;
use Src\Billing\Domain\Entities\BillingProfile;

it('ConsultBillingProfileUseCase returns profile or null', function () {
    $repo = Mockery::mock(BillingProfileRepositoryInterface::class);
    $repo->shouldReceive('getProfile')
        ->once()
        ->andReturn(null);

    $useCase = new ConsultBillingProfileUseCase($repo);
    $result = $useCase->execute();

    expect($result)->toBeNull();
});

it('UpdateBillingProfileUseCase creates or updates profile via repository', function () {
    $repo = Mockery::mock(BillingProfileRepositoryInterface::class);

    $dto = new UpdateBillingProfileData(
        legal_name: 'Tech Store Ltd',
        tax_id: '12345678-9',
        billing_email: 'finance@techstore.com',
        phone: '+1234567890',
        address_line_1: '123 Tech Blvd',
        city: 'Miami',
        state: 'FL',
        postal_code: '33101',
        country: 'USA',
        invoice_prefix: 'INV-',
        next_invoice_number: 100
    );

    $repo->shouldReceive('getProfile')
        ->once()
        ->andReturn(null);

    $repo->shouldReceive('save')
        ->once()
        ->with(Mockery::type(BillingProfile::class))
        ->andReturnUsing(fn (BillingProfile $p) => $p);

    $useCase = new UpdateBillingProfileUseCase($repo);
    $profile = $useCase->execute($dto);

    expect($profile->legalName())->toBe('Tech Store Ltd')
        ->and($profile->taxId()->value())->toBe('12345678-9')
        ->and($profile->invoicePrefix())->toBe('INV-')
        ->and($profile->nextInvoiceNumber())->toBe(100);
});
