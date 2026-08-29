<?php

namespace App\Traits;

use App\Models\Actor;
use App\Models\Otp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait Helpers
{
    protected static function normalizeOtp(string|int $otp): string
    {
        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    protected static function otpCode(string $type, int $actor_id, string $channel, string $guard): string
    {
        $token = self::normalizeOtp(random_int(111111, 999999));

        Otp::create([
            'token' => $token,
            'actor_id' => $actor_id,
            'guard' => $guard,
            'type' => $type,
            'channel' => $channel,
            'expires_at' => now()->addMinutes(10),
        ]);

        return $token;
    }

    protected static function apiToken(Actor $actor, string $oauth_name): string
    {
        return $actor->createToken($oauth_name)->accessToken;
    }

    protected static function base64ImageDecode(?string $base64_image, string $variant = 'generic'): ?string
    {
        $binary = static::decodeDataUrlBinary($base64_image);

        if ($binary === null) {
            return null;
        }

        return static::storeImageBinary($binary['contents'], $binary['extension'], $variant);
    }

    protected static function storeUploadedImage(?UploadedFile $file, string $variant = 'generic'): ?string
    {
        if (! $file || ! $file->isValid()) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $contents = $file->get();

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return static::storeImageBinary($contents, $extension, $variant);
    }

    protected static function storeImageBinary(string $contents, string $extension, string $variant = 'generic'): ?string
    {
        $optimized = static::optimizeImageBinary($contents, $extension, $variant);
        $extension = $optimized['extension'];
        $contents = $optimized['contents'];

        $fileName = Str::uuid() . '.' . $extension;
        $filePath = "uploads/images/{$fileName}";

        $stored = Storage::disk('public')->put($filePath, $contents);

        if (! $stored) {
            return null;
        }

        return static::storagePublicUrl($filePath);
    }

    protected static function decodeDataUrlBinary(?string $dataUrl): ?array
    {
        if (! $dataUrl) {
            return null;
        }

        $dataUrl = trim($dataUrl);

        if (! preg_match('/^data:image\/([a-zA-Z0-9+.-]+)(;[^,]*)?;base64,/i', $dataUrl, $matches)) {
            return null;
        }

        $extension = strtolower($matches[1]);
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        if ($extension === 'svg+xml') {
            return null;
        }
        if (! in_array($extension, ['png', 'jpg', 'gif', 'webp'], true)) {
            return null;
        }

        $imageData = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $decoded = base64_decode($imageData, true);

        if ($decoded === false || $decoded === '') {
            return null;
        }

        return [
            'contents' => $decoded,
            'extension' => $extension,
        ];
    }

    /**
     * Crop/scale raster images so they fit the slot they will be shown in.
     *
     * @return array{contents: string, extension: string}
     */
    protected static function optimizeImageBinary(string $contents, string $extension, string $variant = 'generic'): array
    {
        if (! function_exists('imagecreatefromstring')) {
            return ['contents' => $contents, 'extension' => $extension];
        }

        $source = @imagecreatefromstring($contents);
        if ($source === false) {
            return ['contents' => $contents, 'extension' => $extension];
        }

        $spec = static::imageVariantSpec($variant);
        $srcW = imagesx($source);
        $srcH = imagesy($source);

        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($source);

            return ['contents' => $contents, 'extension' => $extension];
        }

        $targetW = $spec['width'];
        $targetH = $spec['height'];
        $fit = $spec['fit'];

        if ($fit === 'cover') {
            $targetRatio = $targetW / $targetH;
            $srcRatio = $srcW / $srcH;

            if ($srcRatio > $targetRatio) {
                $cropW = (int) round($srcH * $targetRatio);
                $cropH = $srcH;
                $cropX = (int) max(0, round(($srcW - $cropW) / 2));
                $cropY = 0;
            } else {
                $cropW = $srcW;
                $cropH = (int) round($srcW / $targetRatio);
                $cropX = 0;
                $cropY = (int) max(0, round(($srcH - $cropH) / 2));
            }

            $outW = min($targetW, $cropW);
            $outH = min($targetH, $cropH);
        } else {
            $scale = min($targetW / $srcW, $targetH / $srcH, 1);
            $cropX = 0;
            $cropY = 0;
            $cropW = $srcW;
            $cropH = $srcH;
            $outW = max(1, (int) round($srcW * $scale));
            $outH = max(1, (int) round($srcH * $scale));
        }

        $dest = imagecreatetruecolor($outW, $outH);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefilledrectangle($dest, 0, 0, $outW, $outH, $transparent);
        imagealphablending($dest, true);
        imagecopyresampled($dest, $source, 0, 0, $cropX, $cropY, $outW, $outH, $cropW, $cropH);
        imagedestroy($source);

        ob_start();
        $outputExtension = 'jpg';
        if (function_exists('imagewebp')) {
            imagewebp($dest, null, 82);
            $outputExtension = 'webp';
        } else {
            imagejpeg($dest, null, 82);
        }
        $optimized = ob_get_clean();
        imagedestroy($dest);

        if (! is_string($optimized) || $optimized === '') {
            return ['contents' => $contents, 'extension' => $extension];
        }

        return ['contents' => $optimized, 'extension' => $outputExtension];
    }

    protected static function imageVariantSpec(string $variant): array
    {
        return match ($variant) {
            'profile' => ['width' => 512, 'height' => 512, 'fit' => 'cover'],
            'destination' => ['width' => 1280, 'height' => 800, 'fit' => 'cover'],
            'tour' => ['width' => 1600, 'height' => 1000, 'fit' => 'cover'],
            'hero' => ['width' => 1920, 'height' => 1080, 'fit' => 'cover'],
            'logo' => ['width' => 800, 'height' => 800, 'fit' => 'contain'],
            default => ['width' => 1600, 'height' => 1000, 'fit' => 'cover'],
        };
    }

    protected static function isLoopbackHost(?string $host): bool
    {
        if ($host === null || $host === '') {
            return true;
        }

        $host = strtolower(trim($host, '[]'));

        return in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.localhost');
    }

    /**
     * Public origin for /storage files. Never emit a loopback host when APP_URL
     * (or the incoming request) is a real public hostname — that is what broke
     * production uploads behind `php artisan serve` on 127.0.0.1:8000.
     */
    protected static function publicAssetBase(): string
    {
        $configured = rtrim((string) (config('custom.urls.backend_url') ?: config('app.url')), '/');
        $configuredHost = parse_url($configured, PHP_URL_HOST);
        $configuredIsPublic = $configured !== '' && ! static::isLoopbackHost($configuredHost);

        $request = request();
        $requestHost = $request?->getHost() ?: '';
        $requestBase = $requestHost !== '' ? rtrim($request->getSchemeAndHttpHost(), '/') : '';
        $requestIsPublic = $requestHost !== '' && ! static::isLoopbackHost($requestHost);

        if ($configuredIsPublic) {
            return $configured;
        }

        if ($requestIsPublic) {
            return $requestBase;
        }

        return $requestBase !== '' ? $requestBase : $configured;
    }

    protected static function toStoredMediaPath(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'data:')) {
            return $url === '' ? null : $url;
        }

        $path = $url;
        if (! str_starts_with($url, '/')) {
            $parsedPath = parse_url($url, PHP_URL_PATH);
            $path = is_string($parsedPath) ? $parsedPath : $url;
        }

        $path = preg_replace('#/+#', '/', str_replace('\\', '/', $path)) ?: '';
        if (str_contains($path, '/storage/')) {
            $relative = ltrim((string) preg_replace('#^.*?/storage/#', '', $path), '/');

            return $relative !== '' ? '/storage/'.$relative : $url;
        }

        return $url;
    }

    protected static function storagePublicUrl(string $filePath): string
    {
        $filePath = ltrim(str_replace('\\', '/', $filePath), '/');

        return static::publicAssetBase() . '/storage/' . $filePath;
    }

    protected static function normalizePublicUrl(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'data:')) {
            return $url === '' ? null : $url;
        }

        if (str_starts_with($url, '/storage/')) {
            return static::publicAssetBase() . $url;
        }

        $parts = parse_url($url);
        if (! isset($parts['path'])) {
            return $url;
        }

        $path = preg_replace('#/+#', '/', $parts['path'] ?? '') ?: '';
        if (str_contains($path, '/storage/')) {
            $relative = ltrim((string) preg_replace('#^.*?/storage/#', '', $path), '/');

            return static::storagePublicUrl($relative);
        }

        if (! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $normalized = $parts['scheme'] . '://' . $parts['host'];
        if (isset($parts['port'])) {
            $normalized .= ':' . $parts['port'];
        }
        $normalized .= $path;

        if (isset($parts['query'])) {
            $normalized .= '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $normalized .= '#' . $parts['fragment'];
        }

        return $normalized;
    }

    protected static function decodeImageUrl(?string $url, string $variant = 'generic'): ?string
    {
        $candidate = static::extractImageCandidate($url);

        if ($candidate === null || $candidate === '') {
            return null;
        }

        if (str_starts_with($candidate, 'data:')) {
            return static::base64ImageDecode($candidate, $variant);
        }

        return static::normalizePublicUrl($candidate);
    }

    protected static function extractImageCandidate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            foreach (['url', 'uri', 'src', 'image', 'data'] as $key) {
                if (! empty($value[$key]) && is_string($value[$key])) {
                    $nested = $value[$key];
                    if ($key === 'data' && isset($value['mimeType']) && ! str_starts_with($nested, 'data:')) {
                        return 'data:' . $value['mimeType'] . ';base64,' . $nested;
                    }

                    return static::extractImageCandidate($nested) ?? $nested;
                }
            }

            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return static::extractImageCandidate($decoded);
            }
        }

        if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
            $decoded = json_decode($trimmed, true);
            if (is_string($decoded)) {
                return static::extractImageCandidate($decoded);
            }
        }

        return $trimmed;
    }

    protected static function persistStoredImageValue(mixed $value, string $variant = 'generic'): ?string
    {
        $candidate = static::extractImageCandidate($value);

        if ($candidate === null || $candidate === '') {
            return null;
        }

        if (str_starts_with($candidate, 'data:')) {
            return static::base64ImageDecode($candidate, $variant);
        }

        return static::toStoredMediaPath($candidate);
    }

    protected static function persistLandingCmsImages(array $content): array
    {
        if (! empty($content['hero']['backgroundImage'])) {
            $content['hero']['backgroundImage'] = static::persistCmsImageValue($content['hero']['backgroundImage'], 'hero');
        }

        if (! empty($content['cta']['image'])) {
            $content['cta']['image'] = static::persistCmsImageValue($content['cta']['image'], 'destination');
        }

        foreach (['destinations', 'regions'] as $section) {
            if (! isset($content[$section]['items']) || ! is_array($content[$section]['items'])) {
                continue;
            }

            foreach ($content[$section]['items'] as $index => $item) {
                if (! is_array($item) || empty($item['image'])) {
                    continue;
                }

                $content[$section]['items'][$index]['image'] = static::persistCmsImageValue($item['image'], 'destination');
            }
        }

        return $content;
    }

    protected static function persistCmsImageValue(mixed $value, string $variant): string
    {
        $candidate = static::extractImageCandidate($value) ?? '';

        if ($candidate === '') {
            return '';
        }

        if (str_starts_with($candidate, 'data:')) {
            return static::base64ImageDecode($candidate, $variant) ?? '';
        }

        return static::toStoredMediaPath($candidate) ?? '';
    }

    protected static function normalizeLandingCmsUrls(array $content): array
    {
        if (! empty($content['hero']['backgroundImage'])) {
            $content['hero']['backgroundImage'] = static::normalizePublicUrl($content['hero']['backgroundImage'])
                ?? $content['hero']['backgroundImage'];
        }

        if (! empty($content['cta']['image'])) {
            $content['cta']['image'] = static::normalizePublicUrl($content['cta']['image'])
                ?? $content['cta']['image'];
        }

        foreach (['destinations', 'regions'] as $section) {
            if (! isset($content[$section]['items']) || ! is_array($content[$section]['items'])) {
                continue;
            }

            foreach ($content[$section]['items'] as $index => $item) {
                if (! is_array($item) || empty($item['image'])) {
                    continue;
                }

                $content[$section]['items'][$index]['image'] = static::normalizePublicUrl($item['image'])
                    ?? $item['image'];
            }
        }

        return $content;
    }

    protected static function persistActorProfileImage(Actor $actor): void
    {
        $raw = $actor->getRawOriginal('profile_image');
        $candidate = static::extractImageCandidate($raw ?? $actor->profile_image);

        if (! $candidate) {
            return;
        }

        if (! str_starts_with($candidate, 'data:') && is_string($raw) && ! str_starts_with(trim($raw), '{') && ! str_starts_with(trim($raw), '[')) {
            return;
        }

        $persisted = static::persistStoredImageValue($candidate, 'profile');
        $actor->forceFill(['profile_image' => $persisted])->saveQuietly();
        $actor->refresh();
    }

    protected static function normalizeItineraryForOutput(array $itinerary): array
    {
        return array_values(array_map(function ($day) {
            if (! is_array($day)) {
                return $day;
            }

            $imageUrl = $day['imageUrl'] ?? $day['image_url'] ?? null;
            if ($imageUrl) {
                $day['imageUrl'] = static::decodeImageUrl($imageUrl, 'tour');
                unset($day['image_url']);
            }

            return $day;
        }, $itinerary));
    }

    protected static function deleteImage(?string $image_path): bool
    {
        if (! $image_path) {
            return false;
        }

        try {
            // Extract just the file path from the full URL if it's a URL
            $path = parse_url($image_path, PHP_URL_PATH);
            if ($path) {
                $path = str_replace('/storage/', '', $path);
            } else {
                $path = $image_path;
            }

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }
            return false;
        } catch (\Exception $e) {
            logger()->error('Failed to delete image', ['error' => $e->getMessage(), 'path' => $image_path]);
            return false;
        }
    }

    /**
     * Safely decode a JSON string into an array. Returns null on failure.
     */
    protected static function decodeJsonArray(mixed $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
    }

    protected static function findActorByEmailOrPhone(string $modelClass, string $emailOrPhone): ?Actor
    {
        $emailOrPhone = trim($emailOrPhone);

        return $modelClass::query()
            ->where(function ($query) use ($emailOrPhone) {
                $query->where('email', $emailOrPhone)
                    ->orWhere('phone_number', $emailOrPhone);
            })
            ->first();
    }
}
