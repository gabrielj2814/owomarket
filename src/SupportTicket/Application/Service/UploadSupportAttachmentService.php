<?php

declare(strict_types=1);

namespace Src\SupportTicket\Application\Service;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UploadSupportAttachmentService
{
    private const ALLOWED_IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'];

    private const ALLOWED_VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'ogg'];

    private const MAX_FILE_SIZE_KB = 51200; // 50MB

    /**
     * @param  array<UploadedFile>  $files
     * @return array<array{url: string, type: 'image'|'video'|'file', original_name: string, size_bytes: int, mime_type: string}>
     */
    public function uploadMultiple(array $files): array
    {
        $uploaded = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploaded[] = $this->uploadSingle($file);
            }
        }

        return $uploaded;
    }

    /**
     * @return array{url: string, type: 'image'|'video'|'file', original_name: string, size_bytes: int, mime_type: string}
     */
    public function uploadSingle(UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new Exception("El archivo subido no es válido: {$file->getClientOriginalName()}", 422);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $size = $file->getSize();

        $type = 'file';
        if (in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true) || str_starts_with($mime, 'image/')) {
            $type = 'image';
        } elseif (in_array($extension, self::ALLOWED_VIDEO_EXTENSIONS, true) || str_starts_with($mime, 'video/')) {
            $type = 'video';
        }

        $fileName = 'support_'.date('Ymd_His').'_'.Str::random(12).'.'.$extension;
        $path = $file->storeAs('support-attachments', $fileName, 'public');

        $url = Storage::disk('public')->url($path);

        return [
            'url' => $url,
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'size_bytes' => $size,
            'mime_type' => $mime,
        ];
    }
}
