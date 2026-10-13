<?php

namespace App\Services;

use RuntimeException;

class FaceMatchService
{
    private $threshold = 68;

    public function compareDataUrlToProfile(string $capturedImageDataUrl, array $user): array
    {
        $reference = $this->resolveReferenceMeta($user);
        if (!$reference || !is_file($reference['path'])) {
            throw new RuntimeException('Selected employee does not have a saved face reference image.');
        }

        $capturedHash = $this->buildHashFromCapturedDataUrl($capturedImageDataUrl);
        return array_merge(
            $this->compareHashes($capturedHash, $this->buildHashFromPath($reference['path'])),
            ['reference_field' => $reference['field']]
        );
    }

    public function compareDataUrlToCandidates(string $capturedImageDataUrl, array $users): array
    {
        $capturedHash = $this->buildHashFromCapturedDataUrl($capturedImageDataUrl);
        $bestMatch = null;

        foreach ($users as $user) {
            $reference = $this->resolveReferenceMeta($user);
            if (!$reference || !is_file($reference['path'])) {
                continue;
            }

            $comparison = array_merge(
                $this->compareHashes($capturedHash, $this->buildHashFromPath($reference['path'])),
                ['reference_field' => $reference['field']]
            );
            if ($bestMatch === null || (int) $comparison['score'] > (int) $bestMatch['score']) {
                $bestMatch = array_merge($comparison, [
                    'user' => $user,
                ]);
            }
        }

        if ($bestMatch === null) {
            throw new RuntimeException('No active employee face reference images are available for camera matching.');
        }

        return $bestMatch;
    }

    private function resolveReferencePath(array $user): ?string
    {
        $reference = $this->resolveReferenceMeta($user);
        return $reference ? $reference['path'] : null;
    }

    private function resolveReferenceMeta(array $user): ?array
    {
        foreach (['face_reference_thumbnail', 'face_reference_image'] as $field) {
            $value = trim((string) ($user[$field] ?? ''));
            if ($value === '') {
                continue;
            }

            $normalized = str_replace('/', DIRECTORY_SEPARATOR, ltrim($value, '/\\'));
            return [
                'field' => $field,
                'path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'web' . DIRECTORY_SEPARATOR . 'work-management' . DIRECTORY_SEPARATOR . $normalized,
            ];
        }

        return null;
    }

    private function averageHash($image): string
    {
        $small = imagescale($image, 8, 8, IMG_BILINEAR_FIXED);
        if (!$small) {
            throw new RuntimeException('Unable to normalize image for comparison.');
        }

        $pixels = [];
        $total = 0;
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;
                $gray = (int) round(($red + $green + $blue) / 3);
                $pixels[] = $gray;
                $total += $gray;
            }
        }

        imagedestroy($small);

        $average = $total / 64;
        $hash = '';
        foreach ($pixels as $pixel) {
            $hash .= $pixel >= $average ? '1' : '0';
        }

        return $hash;
    }

    private function buildHashFromCapturedDataUrl(string $capturedImageDataUrl): string
    {
        if (!preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $capturedImageDataUrl)) {
            throw new RuntimeException('Captured camera image is invalid.');
        }

        $capturedBinary = base64_decode(substr($capturedImageDataUrl, strpos($capturedImageDataUrl, ',') + 1), true);
        if ($capturedBinary === false) {
            throw new RuntimeException('Unable to decode captured image.');
        }

        $capturedImage = @imagecreatefromstring($capturedBinary);
        if (!$capturedImage) {
            throw new RuntimeException('Unable to load captured camera image.');
        }

        $hash = $this->averageHash($capturedImage);
        imagedestroy($capturedImage);

        return $hash;
    }

    private function buildHashFromPath(string $path): string
    {
        $referenceBinary = @file_get_contents($path);
        $referenceImage = $referenceBinary !== false ? @imagecreatefromstring($referenceBinary) : false;
        if (!$referenceImage) {
            throw new RuntimeException('Unable to load one of the profile images for comparison.');
        }

        $hash = $this->averageHash($referenceImage);
        imagedestroy($referenceImage);

        return $hash;
    }

    private function compareHashes(string $capturedHash, string $referenceHash): array
    {
        $distance = $this->hammingDistance($capturedHash, $referenceHash);
        $score = max(0, 100 - (int) round(($distance / max(strlen($capturedHash), 1)) * 100));
        $matched = $score >= $this->threshold;

        return [
            'matched' => $matched,
            'score' => $score,
            'threshold' => $this->threshold,
        ];
    }

    private function hammingDistance(string $left, string $right): int
    {
        $distance = 0;
        $length = min(strlen($left), strlen($right));
        for ($index = 0; $index < $length; $index++) {
            if ($left[$index] !== $right[$index]) {
                $distance++;
            }
        }

        return $distance + abs(strlen($left) - strlen($right));
    }
}
