<?php

namespace Src\Admin\Infrastructure\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Admin\Application\Contracts\Services\AvatarStorageInterface;
use Src\Shared\Domain\ValueObjects\AvatarUrl;

class LaravelAvatarStorageService implements AvatarStorageInterface
{
    public function uploadAvatar(UploadedFile $file, ?string $oldAvatarUrl = null): AvatarUrl
    {
        if ($oldAvatarUrl) {
            $this->deleteAvatar($oldAvatarUrl);
        }

        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('avatars', $filename, 'public');

        $url = Storage::disk('public')->url($path);

        return AvatarUrl::make($url);
    }

    public function deleteAvatar(string $avatarUrl): void
    {
        if (empty($avatarUrl)) {
            return;
        }

        $path = parse_url($avatarUrl, PHP_URL_PATH);
        if ($path) {
            $relativePath = str_replace('/storage/', '', $path);
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }
    }
}
