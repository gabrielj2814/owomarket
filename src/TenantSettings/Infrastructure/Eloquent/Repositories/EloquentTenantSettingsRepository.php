<?php

declare(strict_types=1);

namespace Src\TenantSettings\Infrastructure\Eloquent\Repositories;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Src\TenantSettings\Application\Repositories\TenantSettingsRepositoryInterface;
use Src\TenantSettings\Domain\Entities\StoreSettings;
use Src\TenantSettings\Domain\Entities\TenantSetting as DomainTenantSetting;
use Src\TenantSettings\Domain\ValueObjects\SettingGroup;
use Src\TenantSettings\Domain\ValueObjects\SettingId;
use Src\TenantSettings\Domain\ValueObjects\SettingKey;
use Src\TenantSettings\Domain\ValueObjects\SettingType;
use Src\TenantSettings\Infrastructure\Eloquent\Models\TenantSetting as EloquentTenantSetting;

final class EloquentTenantSettingsRepository implements TenantSettingsRepositoryInterface
{
    private const KEY_GROUP_MAP = [
        'store_name' => SettingGroup::GENERAL,
        'store_email' => SettingGroup::GENERAL,
        'currency' => SettingGroup::GENERAL,
        'contact_phone' => SettingGroup::GENERAL,
        'address' => SettingGroup::GENERAL,
        'logo_url' => SettingGroup::APPEARANCE,
        'banner_url' => SettingGroup::APPEARANCE,
        'social_facebook' => SettingGroup::SOCIAL,
        'social_instagram' => SettingGroup::SOCIAL,
        'social_whatsapp' => SettingGroup::SOCIAL,
        'social_twitter' => SettingGroup::SOCIAL,
        'seo_title' => SettingGroup::SEO,
        'seo_description' => SettingGroup::SEO,
        'seo_keywords' => SettingGroup::SEO,
    ];

    public function save(DomainTenantSetting $setting): void
    {
        EloquentTenantSetting::query()->updateOrCreate(
            ['key' => $setting->key()->value()],
            [
                'id' => $setting->id()->value(),
                'value' => $setting->value(),
                'type' => $setting->type()->value(),
                'group' => $setting->group()->value(),
            ]
        );
    }

    public function saveMultiple(array $settings): void
    {
        DB::transaction(function () use ($settings) {
            foreach ($settings as $setting) {
                $this->save($setting);
            }
        });
    }

    public function findByKey(SettingKey $key): ?DomainTenantSetting
    {
        $model = EloquentTenantSetting::query()
            ->where('key', $key->value())
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(SettingId $id): ?DomainTenantSetting
    {
        $model = EloquentTenantSetting::query()
            ->where('id', $id->value())
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function listByGroup(SettingGroup $group): array
    {
        return EloquentTenantSetting::query()
            ->where('group', $group->value())
            ->orderBy('key', 'asc')
            ->get()
            ->map(fn (EloquentTenantSetting $m) => $this->toDomain($m))
            ->all();
    }

    public function listAll(): array
    {
        return EloquentTenantSetting::query()
            ->orderBy('group', 'asc')
            ->orderBy('key', 'asc')
            ->get()
            ->map(fn (EloquentTenantSetting $m) => $this->toDomain($m))
            ->all();
    }

    public function delete(SettingKey $key): void
    {
        EloquentTenantSetting::query()
            ->where('key', $key->value())
            ->delete();
    }

    public function getStoreSettings(): StoreSettings
    {
        $records = EloquentTenantSetting::query()->pluck('value', 'key')->all();

        return StoreSettings::fromKeyValueMap($records);
    }

    public function updateStoreSettings(StoreSettings $settings): void
    {
        $map = $settings->toKeyValueMap();

        DB::transaction(function () use ($map) {
            foreach ($map as $key => $value) {
                $group = self::KEY_GROUP_MAP[$key] ?? SettingGroup::GENERAL;

                EloquentTenantSetting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => SettingType::STRING,
                        'group' => $group,
                    ]
                );
            }
        });
    }

    private function toDomain(EloquentTenantSetting $model): DomainTenantSetting
    {
        return new DomainTenantSetting(
            id: SettingId::fromString($model->id),
            key: new SettingKey($model->key),
            value: $model->value,
            type: new SettingType($model->type ?? SettingType::STRING),
            group: new SettingGroup($model->group ?? SettingGroup::GENERAL),
            createdAt: $model->created_at ? DateTimeImmutable::createFromInterface($model->created_at) : null,
            updatedAt: $model->updated_at ? DateTimeImmutable::createFromInterface($model->updated_at) : null
        );
    }
}
