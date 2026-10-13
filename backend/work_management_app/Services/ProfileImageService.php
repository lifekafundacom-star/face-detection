<?php

namespace App\Services;

use RuntimeException;

class ProfileImageService
{
    private const MAX_UPLOAD_BYTES = 4194304;
    private const THUMB_SIZE = 50;
    private const CROPPED_SIZE = 500;

    public function saveForUser(int $userId, array $file, ?string $croppedData, array $existingUser = []): array
    {
        $this->assertGdAvailable();

        $source = $this->normalizeUploadSource($file, $croppedData);
        $directories = $this->directories();
        $this->ensureDirectory($directories['original']);
        $this->ensureDirectory($directories['thumb']);

        $extension = $this->extensionForMime($source['mime']);
        $baseName = 'user-' . $userId . '-' . bin2hex(random_bytes(8));

        $originalRelativePath = '/uploads/profile/' . $baseName . '.' . $extension;
        $thumbRelativePath = '/uploads/profile/thumbs/' . $baseName . '.' . $extension;

        $originalAbsolutePath = $directories['root'] . $originalRelativePath;
        $thumbAbsolutePath = $directories['root'] . $thumbRelativePath;

        $imageResource = $this->createImageResource($source['bytes'], $source['mime']);
        if (!$imageResource) {
            throw new RuntimeException('Uploaded file is not a valid image.');
        }

        $sourceWidth = imagesx($imageResource);
        $sourceHeight = imagesy($imageResource);

        $croppedOriginal = $this->centerCropAndResize($imageResource, $sourceWidth, $sourceHeight, self::CROPPED_SIZE, self::CROPPED_SIZE, $source['mime']);
        $thumbnail = $this->centerCropAndResize($imageResource, $sourceWidth, $sourceHeight, self::THUMB_SIZE, self::THUMB_SIZE, $source['mime']);

        $this->saveImageResource($croppedOriginal, $originalAbsolutePath, $source['mime']);
        $this->saveImageResource($thumbnail, $thumbAbsolutePath, $source['mime']);

        imagedestroy($imageResource);
        imagedestroy($croppedOriginal);
        imagedestroy($thumbnail);

        $this->deleteOldFiles($existingUser, $directories['root']);

        return [
            'profile_image' => $originalRelativePath,
            'profile_thumbnail' => $thumbRelativePath,
            'profile_image_path' => $originalRelativePath,
        ];
    }

    public function generateThumbnailForExisting(string $relativeImagePath, ?string $existingThumbnailPath = null): ?string
    {
        $this->assertGdAvailable();
        $directories = $this->directories();
        $absoluteImagePath = $directories['root'] . $relativeImagePath;

        if (!is_file($absoluteImagePath)) {
            return null;
        }

        if ($existingThumbnailPath && is_file($directories['root'] . $existingThumbnailPath)) {
            return $existingThumbnailPath;
        }

        $mime = $this->mimeFromFile($absoluteImagePath);
        $bytes = (string) file_get_contents($absoluteImagePath);
        $resource = $this->createImageResource($bytes, $mime);
        if (!$resource) {
            return null;
        }

        $this->ensureDirectory($directories['thumb']);
        $extension = $this->extensionForMime($mime);
        $baseName = pathinfo($absoluteImagePath, PATHINFO_FILENAME);
        $thumbRelativePath = '/uploads/profile/thumbs/' . $baseName . '.' . $extension;
        $thumbAbsolutePath = $directories['root'] . $thumbRelativePath;

        $thumbnail = $this->centerCropAndResize($resource, imagesx($resource), imagesy($resource), self::THUMB_SIZE, self::THUMB_SIZE, $mime);
        $this->saveImageResource($thumbnail, $thumbAbsolutePath, $mime);

        imagedestroy($resource);
        imagedestroy($thumbnail);

        return $thumbRelativePath;
    }

    public function saveFaceReferenceForUser(int $userId, string $capturedDataUrl, array $existingUser = []): array
    {
        $this->assertGdAvailable();

        $source = $this->decodeDataUrl($capturedDataUrl);
        $this->assertAllowedMime($source['mime']);
        if (strlen($source['bytes']) > self::MAX_UPLOAD_BYTES) {
            throw new RuntimeException('Captured face reference image is too large.');
        }

        $directories = $this->directories();
        $this->ensureDirectory($directories['face_original']);
        $this->ensureDirectory($directories['face_thumb']);

        $extension = $this->extensionForMime($source['mime']);
        $baseName = 'face-user-' . $userId . '-' . bin2hex(random_bytes(8));

        $originalRelativePath = '/uploads/face-reference/' . $baseName . '.' . $extension;
        $thumbRelativePath = '/uploads/face-reference/thumbs/' . $baseName . '.' . $extension;

        $imageResource = $this->createImageResource($source['bytes'], $source['mime']);
        if (!$imageResource) {
            throw new RuntimeException('Captured face image is not valid.');
        }

        $sourceWidth = imagesx($imageResource);
        $sourceHeight = imagesy($imageResource);
        $croppedOriginal = $this->centerCropAndResize($imageResource, $sourceWidth, $sourceHeight, self::CROPPED_SIZE, self::CROPPED_SIZE, $source['mime']);
        $thumbnail = $this->centerCropAndResize($imageResource, $sourceWidth, $sourceHeight, self::THUMB_SIZE, self::THUMB_SIZE, $source['mime']);

        $this->saveImageResource($croppedOriginal, $directories['root'] . $originalRelativePath, $source['mime']);
        $this->saveImageResource($thumbnail, $directories['root'] . $thumbRelativePath, $source['mime']);

        imagedestroy($imageResource);
        imagedestroy($croppedOriginal);
        imagedestroy($thumbnail);

        $this->deleteOldFiles($existingUser, $directories['root'], ['face_reference_image', 'face_reference_thumbnail']);

        return [
            'face_reference_image' => $originalRelativePath,
            'face_reference_thumbnail' => $thumbRelativePath,
        ];
    }

    private function normalizeUploadSource(array $file, ?string $croppedData): array
    {
        if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Profile image upload failed.');
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_UPLOAD_BYTES) {
            throw new RuntimeException('Profile image must be 4MB or smaller.');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $bytes = '';
        if ($tmpName !== '' && is_file($tmpName)) {
            $bytes = (string) file_get_contents($tmpName);
        }

        $mime = $this->mimeFromBinary($bytes);
        $this->assertAllowedMime($mime);

        if ($croppedData) {
            $cropped = $this->decodeDataUrl($croppedData);
            $this->assertAllowedMime($cropped['mime']);
            if (strlen($cropped['bytes']) > self::MAX_UPLOAD_BYTES) {
                throw new RuntimeException('Cropped image is too large.');
            }

            return $cropped;
        }

        return [
            'bytes' => $bytes,
            'mime' => $mime,
        ];
    }

    private function decodeDataUrl(string $dataUrl): array
    {
        if (!preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $dataUrl, $matches)) {
            throw new RuntimeException('Invalid cropped image data.');
        }

        $bytes = base64_decode($matches[2], true);
        if ($bytes === false) {
            throw new RuntimeException('Could not decode cropped image.');
        }

        return [
            'bytes' => $bytes,
            'mime' => strtolower($matches[1]),
        ];
    }

    private function centerCropAndResize($source, int $sourceWidth, int $sourceHeight, int $targetWidth, int $targetHeight, string $mime)
    {
        $cropSize = min($sourceWidth, $sourceHeight);
        $srcX = (int) floor(($sourceWidth - $cropSize) / 2);
        $srcY = (int) floor(($sourceHeight - $cropSize) / 2);

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $srcX,
            $srcY,
            $targetWidth,
            $targetHeight,
            $cropSize,
            $cropSize
        );

        return $target;
    }

    private function createImageResource(string $bytes, string $mime)
    {
        if ($bytes === '') {
            throw new RuntimeException('Uploaded image is empty.');
        }

        if (function_exists('imagecreatefromstring')) {
            $resource = @imagecreatefromstring($bytes);
            if ($resource !== false) {
                return $resource;
            }
        }

        throw new RuntimeException('Could not read uploaded image.');
    }

    private function saveImageResource($resource, string $path, string $mime): void
    {
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($resource, $path, 90);
                return;
            case 'image/png':
                imagepng($resource, $path, 6);
                return;
            case 'image/webp':
                if (!function_exists('imagewebp')) {
                    throw new RuntimeException('WEBP support is not enabled on this server.');
                }
                imagewebp($resource, $path, 90);
                return;
            default:
                throw new RuntimeException('Unsupported image format.');
        }
    }

    private function directories(): array
    {
        $root = dirname(__DIR__, 2) . '/web/work-management';

        return [
            'root' => $root,
            'original' => $root . '/uploads/profile',
            'thumb' => $root . '/uploads/profile/thumbs',
            'face_original' => $root . '/uploads/face-reference',
            'face_thumb' => $root . '/uploads/face-reference/thumbs',
        ];
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Could not prepare image upload directory.');
        }
    }

    private function deleteOldFiles(array $existingUser, string $root, ?array $keys = null): void
    {
        foreach (($keys ?: ['profile_image', 'profile_thumbnail', 'profile_image_path']) as $key) {
            $relativePath = (string) ($existingUser[$key] ?? '');
            if ($relativePath === '' || strpos($relativePath, '/uploads/') !== 0) {
                continue;
            }

            $absolutePath = $root . $relativePath;
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function mimeFromBinary(string $bytes): string
    {
        if ($bytes === '') {
            throw new RuntimeException('Uploaded image is empty.');
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mime = (string) finfo_buffer($finfo, $bytes);
                finfo_close($finfo);
                return strtolower($mime);
            }
        }

        $info = @getimagesizefromstring($bytes);
        if (is_array($info) && !empty($info['mime'])) {
            return strtolower((string) $info['mime']);
        }

        throw new RuntimeException('Could not detect uploaded image type.');
    }

    private function mimeFromFile(string $path): string
    {
        $bytes = (string) file_get_contents($path);
        return $this->mimeFromBinary($bytes);
    }

    private function extensionForMime(string $mime): string
    {
        switch ($mime) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/webp':
                return 'webp';
            default:
                throw new RuntimeException('Unsupported image format.');
        }
    }

    private function assertAllowedMime(string $mime): void
    {
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Only JPG, JPEG, PNG, and WEBP images are allowed.');
        }
    }

    private function assertGdAvailable(): void
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Image processing support is not available on this server.');
        }
    }
}
