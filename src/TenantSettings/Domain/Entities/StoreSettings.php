<?php

declare(strict_types=1);

namespace Src\TenantSettings\Domain\Entities;

final class StoreSettings
{
    public function __construct(
        private string $storeName = 'Mi Tienda Online',
        private string $storeEmail = 'contacto@tienda.com',
        private string $currency = 'USD',
        private ?string $contactPhone = null,
        private ?string $address = null,
        private ?string $logoUrl = null,
        private ?string $bannerUrl = null,
        private ?string $socialFacebook = null,
        private ?string $socialInstagram = null,
        private ?string $socialWhatsapp = null,
        private ?string $socialTwitter = null,
        private ?string $seoTitle = null,
        private ?string $seoDescription = null,
        private ?string $seoKeywords = null
    ) {}

    public static function fromKeyValueMap(array $map): self
    {
        return new self(
            storeName: (string) ($map['store_name'] ?? 'Mi Tienda Online'),
            storeEmail: (string) ($map['store_email'] ?? 'contacto@tienda.com'),
            currency: (string) ($map['currency'] ?? 'USD'),
            contactPhone: isset($map['contact_phone']) ? (string) $map['contact_phone'] : null,
            address: isset($map['address']) ? (string) $map['address'] : null,
            logoUrl: isset($map['logo_url']) ? (string) $map['logo_url'] : null,
            bannerUrl: isset($map['banner_url']) ? (string) $map['banner_url'] : null,
            socialFacebook: isset($map['social_facebook']) ? (string) $map['social_facebook'] : null,
            socialInstagram: isset($map['social_instagram']) ? (string) $map['social_instagram'] : null,
            socialWhatsapp: isset($map['social_whatsapp']) ? (string) $map['social_whatsapp'] : null,
            socialTwitter: isset($map['social_twitter']) ? (string) $map['social_twitter'] : null,
            seoTitle: isset($map['seo_title']) ? (string) $map['seo_title'] : null,
            seoDescription: isset($map['seo_description']) ? (string) $map['seo_description'] : null,
            seoKeywords: isset($map['seo_keywords']) ? (string) $map['seo_keywords'] : null,
        );
    }

    public function toKeyValueMap(): array
    {
        return [
            'store_name' => $this->storeName,
            'store_email' => $this->storeEmail,
            'currency' => $this->currency,
            'contact_phone' => $this->contactPhone,
            'address' => $this->address,
            'logo_url' => $this->logoUrl,
            'banner_url' => $this->bannerUrl,
            'social_facebook' => $this->socialFacebook,
            'social_instagram' => $this->socialInstagram,
            'social_whatsapp' => $this->socialWhatsapp,
            'social_twitter' => $this->socialTwitter,
            'seo_title' => $this->seoTitle,
            'seo_description' => $this->seoDescription,
            'seo_keywords' => $this->seoKeywords,
        ];
    }

    public function storeName(): string
    {
        return $this->storeName;
    }

    public function storeEmail(): string
    {
        return $this->storeEmail;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function contactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function logoUrl(): ?string
    {
        return $this->logoUrl;
    }

    public function bannerUrl(): ?string
    {
        return $this->bannerUrl;
    }

    public function socialFacebook(): ?string
    {
        return $this->socialFacebook;
    }

    public function socialInstagram(): ?string
    {
        return $this->socialInstagram;
    }

    public function socialWhatsapp(): ?string
    {
        return $this->socialWhatsapp;
    }

    public function socialTwitter(): ?string
    {
        return $this->socialTwitter;
    }

    public function seoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function seoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function seoKeywords(): ?string
    {
        return $this->seoKeywords;
    }

    public function toArray(): array
    {
        return [
            'general' => [
                'store_name' => $this->storeName,
                'store_email' => $this->storeEmail,
                'currency' => $this->currency,
                'contact_phone' => $this->contactPhone,
                'address' => $this->address,
            ],
            'appearance' => [
                'logo_url' => $this->logoUrl,
                'banner_url' => $this->bannerUrl,
            ],
            'social' => [
                'facebook' => $this->socialFacebook,
                'instagram' => $this->socialInstagram,
                'whatsapp' => $this->socialWhatsapp,
                'twitter' => $this->socialTwitter,
            ],
            'seo' => [
                'meta_title' => $this->seoTitle,
                'meta_description' => $this->seoDescription,
                'meta_keywords' => $this->seoKeywords,
            ],
        ];
    }
}
