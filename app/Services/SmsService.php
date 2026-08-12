<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $phoneNumber, string $message): bool
    {
        if (! config('services.sms.enabled')) {
            Log::info('SMS notification skipped (disabled)', [
                'phone_number' => $phoneNumber,
                'message' => $message,
            ]);

            return false;
        }

        $recipient = $this->normalizePhoneNumber($phoneNumber);
        $apiKey = config('services.sms.api_key');
        $endpoint = config('services.sms.endpoint');

        if (! $recipient || ! $apiKey || ! $endpoint) {
            Log::warning('SMS notification skipped (missing configuration)', [
                'phone_number' => $phoneNumber,
            ]);

            return false;
        }

        $response = Http::withHeaders([
            'api-key' => $apiKey,
        ])->post($endpoint, [
            'sender' => config('services.sms.sender_id'),
            'message' => $message,
            'recipients' => [$recipient],
        ]);

        if (! $response->successful()) {
            Log::error('SMS send failed', [
                'phone_number' => $recipient,
                'response' => $response->json(),
            ]);

            return false;
        }

        return true;
    }

    protected function normalizePhoneNumber(string $phoneNumber): ?string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '233' . substr($digits, 1);
        }

        if (str_starts_with($digits, '233')) {
            return $digits;
        }

        return $digits;
    }
}
