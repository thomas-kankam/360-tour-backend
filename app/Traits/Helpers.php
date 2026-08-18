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

    protected static function base64ImageDecode(?string $base64_image): ?string
    {
        if (! $base64_image) {
            return null;
        }

        if (! preg_match('/^data:image\/(png|jpg|jpeg|gif|webp);base64,/', $base64_image, $matches)) {
            return null;
        }

        $extension  = $matches[1];
        $image_data = substr($base64_image, strpos($base64_image, ',') + 1);

        $decoded = base64_decode($image_data, true);

        if ($decoded === false) {
            return null;
        }

        $fileName = Str::uuid() . '.' . $extension;
        $filePath = "uploads/images/{$fileName}";

        Storage::disk('public')->put($filePath, $decoded);

        return static::storagePublicUrl($filePath);
    }

    protected static function storeUploadedImage(?UploadedFile $file): ?string
    {
        if (! $file || ! $file->isValid()) {
            return null;
        }

        $extension = $file->getClientOriginalExtension() ?: $file->extension();
        $fileName = Str::uuid() . '.' . strtolower($extension);
        $filePath = "uploads/images/{$fileName}";

        $stored = Storage::disk('public')->put($filePath, $file->get());

        if (! $stored) {
            return null;
        }

        return static::storagePublicUrl($filePath);
    }

    protected static function storagePublicUrl(string $filePath): string
    {
        $base = rtrim((string) config('custom.urls.backend_url'), '/');

        return "{$base}/storage/{$filePath}";
    }

    protected static function normalizePublicUrl(?string $url): ?string
    {
        if (! $url || str_starts_with($url, 'data:')) {
            return $url;
        }

        $parts = parse_url($url);
        if (! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $path = preg_replace('#/+#', '/', $parts['path'] ?? '');

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

    protected static function decodeImageUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        if (str_starts_with($url, 'data:')) {
            return static::base64ImageDecode($url) ?? $url;
        }

        return static::normalizePublicUrl($url);
    }

    protected static function normalizeItineraryForOutput(array $itinerary): array
    {
        return array_values(array_map(function ($day) {
            if (! is_array($day)) {
                return $day;
            }

            $imageUrl = $day['imageUrl'] ?? $day['image_url'] ?? null;
            if ($imageUrl) {
                $day['imageUrl'] = static::normalizePublicUrl($imageUrl);
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
