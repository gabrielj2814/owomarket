<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\UseCases;

use Src\TenantSettings\Application\DTOs\UpdateStoreSettingsData;
use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\Entities\StoreSettings;

final class UpdateStoreSettingsUseCase
{
    public function __construct(
        private readonly TenantSettingsRepositoryInterface $repository
    ) {}

    public function execute(UpdateStoreSettingsData $dto): StoreSettings
    {
        $current = $this->repository->getStoreSettings();

        $updated = new StoreSettings(
            storeName: $dto->storeName ?? $current->storeName(),
            storeEmail: $dto->storeEmail ?? $current->storeEmail(),
            currency: $dto->currency ?? $current->currency(),
            contactPhone: $dto->contactPhone !== null ? $dto->contactPhone : $current->contactPhone(),
            address: $dto->address !== null ? $dto->address : $current->address(),
            logoUrl: $dto->logoUrl !== null ? $dto->logoUrl : $current->logoUrl(),
            bannerUrl: $dto->bannerUrl !== null ? $dto->bannerUrl : $current->bannerUrl(),
            socialFacebook: $dto->socialFacebook !== null ? $dto->socialFacebook : $current->socialFacebook(),
            socialInstagram: $dto->socialInstagram !== null ? $dto->socialInstagram : $current->socialInstagram(),
            socialWhatsapp: $dto->socialWhatsapp !== null ? $dto->socialWhatsapp : $current->socialWhatsapp(),
            socialTwitter: $dto->socialTwitter !== null ? $dto->socialTwitter : $current->socialTwitter(),
            seoTitle: $dto->seoTitle !== null ? $dto->seoTitle : $current->seoTitle(),
            seoDescription: $dto->seoDescription !== null ? $dto->seoDescription : $current->seoDescription(),
            seoKeywords: $dto->seoKeywords !== null ? $dto->seoKeywords : $current->seoKeywords(),
        );

        $this->repository->updateStoreSettings($updated);

        return $updated;
    }
}
