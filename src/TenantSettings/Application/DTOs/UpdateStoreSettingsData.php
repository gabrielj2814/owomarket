<?php

declare(strict_types=1);

namespace Src\TenantSettings\Application\DTOs;

final class UpdateStoreSettingsData
{
    public function __construct(
        public readonly ?string $storeName = null,
        public readonly ?string $storeEmail = null,
        public readonly ?string $currency = null,
        public readonly ?string $contactPhone = null,
        public readonly ?string $address = null,
        public readonly ?string $logoUrl = null,
        public readonly ?string $bannerUrl = null,
        public readonly ?string $socialFacebook = null,
        public readonly ?string $socialInstagram = null,
        public readonly ?string $socialWhatsapp = null,
        public readonly ?string $socialTwitter = null,
        public readonly ?string $seoTitle = null,
        public readonly ?string $seoDescription = null,
        public readonly ?string $seoKeywords = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            storeName: isset($data['store_name']) ? (string) $data['store_name'] : null,
            storeEmail: isset($data['store_email']) ? (string) $data['store_email'] : null,
            currency: isset($data['currency']) ? (string) $data['currency'] : null,
            contactPhone: isset($data['contact_phone']) ? (string) $data['contact_phone'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            logoUrl: isset($data['logo_url']) ? (string) $data['logo_url'] : null,
            bannerUrl: isset($data['banner_url']) ? (string) $data['banner_url'] : null,
            socialFacebook: isset($data['social_facebook']) ? (string) $data['social_facebook'] : null,
            socialInstagram: isset($data['social_instagram']) ? (string) $data['social_instagram'] : null,
            socialWhatsapp: isset($data['social_whatsapp']) ? (string) $data['social_whatsapp'] : null,
            socialTwitter: isset($data['social_twitter']) ? (string) $data['social_twitter'] : null,
            seoTitle: isset($data['seo_title']) ? (string) $data['seo_title'] : null,
            seoDescription: isset($data['seo_description']) ? (string) $data['seo_description'] : null,
            seoKeywords: isset($data['seo_keywords']) ? (string) $data['seo_keywords'] : null,
        );
    }
}
