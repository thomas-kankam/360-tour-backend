<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaystackService
{
    protected string $secretKey;

    protected string $publicKey;

    protected string $callbackUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key') ?? '';
        $this->publicKey = config('services.paystack.public_key') ?? '';
        $this->callbackUrl = config('services.paystack.callback_url') ?? '';
    }

    public function initializeTransaction(string $email, float $amount, string $currency, array $metadata = []): array
    {
        $reference = '360TG_' . Str::upper(Str::random(12)) . '_' . time();

        $response = Http::withToken($this->secretKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $email,
                'amount' => $this->toMinorUnit($amount, $currency),
                'currency' => strtoupper($currency),
                'reference' => $reference,
                'callback_url' => $this->callbackUrl,
                'metadata' => $metadata,
            ]);

        $body = $response->json();

        if (! $response->successful() || ! ($body['status'] ?? false)) {
            throw new \RuntimeException($body['message'] ?? 'Paystack initialization failed');
        }

        return [
            'reference' => $reference,
            'access_code' => $body['data']['access_code'] ?? null,
            'authorization_url' => $body['data']['authorization_url'] ?? null,
            'raw' => $body,
        ];
    }

    public function verifyTransaction(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get('https://api.paystack.co/transaction/verify/' . $reference);

        $body = $response->json();

        if (! $response->successful() || ! ($body['status'] ?? false)) {
            // Log::info('Paystack verification failed', ['verified' => $body]);
            throw new \RuntimeException($body['message'] ?? 'Paystack verification failed');
        }

        // Log::info('Paystack verified', ['verified' => $body]);

        return $body['data'] ?? [];
    }

    public function validateWebhookSignature(string $payload, ?string $signature): bool
    {
        if (! $signature) {
            // Log::info('Paystack webhook signature validation failed', ['signature' => $signature]);
            return false;
        }

        $computed = hash_hmac('sha512', $payload, $this->secretKey);

        return hash_equals($computed, $signature);
    }

    protected function toMinorUnit(float $amount, string $currency): int
    {
        $zeroDecimal = ['JPY'];

        if (in_array(strtoupper($currency), $zeroDecimal, true)) {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }
}
