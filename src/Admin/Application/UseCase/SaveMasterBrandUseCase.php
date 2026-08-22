<?php

declare(strict_types=1);

namespace Src\Admin\Application\UseCase;

use Exception;
use Illuminate\Support\Str;
use Src\Brand\Infrastructure\Eloquent\Models\CentralBrand;

final class SaveMasterBrandUseCase
{
    /**
     * @param array{
     *     id?: string|null,
     *     name: string,
     *     slug?: string|null,
     *     logo?: string|null,
     *     description?: string|null,
     *     is_active?: bool,
     *     position?: int
     * } $data
     */
    public function execute(array $data): CentralBrand
    {
        $id = $data['id'] ?? null;
        $name = trim($data['name']);
        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($name);

        if ($id) {
            $brand = CentralBrand::find($id);
            if (! $brand) {
                throw new Exception("Marca central '{$id}' no encontrada.", 404);
            }
        } else {
            $brand = new CentralBrand;
            $brand->id = (string) Str::uuid();
        }

        $brand->name = $name;
        $brand->slug = $slug;
        $brand->logo = $data['logo'] ?? $brand->logo;
        $brand->description = $data['description'] ?? $brand->description;
        $brand->is_active = isset($data['is_active']) ? (bool) $data['is_active'] : ($brand->is_active ?? true);
        $brand->position = isset($data['position']) ? (int) $data['position'] : ($brand->position ?? 0);

        $brand->save();

        return $brand;
    }
}
