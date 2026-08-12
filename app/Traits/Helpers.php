<?php

namespace App\Traits;

use App\Models\Actor;
use App\Models\Otp;
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

        return config('custom.urls.backend_url') . "/storage/{$filePath}";
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
