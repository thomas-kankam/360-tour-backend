<?php

namespace Tests\Unit;

use App\Models\Tour;
use App\Services\BookingNotificationService;
use App\Services\BookingService;
use App\Services\PaystackService;
use Tests\TestCase;

class BookingServiceChargeProbe extends BookingService
{
    public function publicResolvePaystackCharge(Tour $tour, int $travelers, ?float $quotedAmount, ?string $requestedCurrency): array
    {
        return $this->resolvePaystackCharge($tour, $travelers, $quotedAmount, $requestedCurrency);
    }
}

class PaystackServiceTest extends TestCase
{
    public function test_normalizes_ghana_currency_aliases(): void
    {
        $paystack = app(PaystackService::class);

        $this->assertSame('GHS', $paystack->normalizeCurrency('ghc'));
        $this->assertSame('GHS', $paystack->normalizeCurrency('GHS'));
        $this->assertSame('USD', $paystack->normalizeCurrency('usd'));
        $this->assertSame('GHS', $paystack->normalizeCurrency(null));
    }

    public function test_defaults_to_ghs_when_requested_currency_is_not_supported(): void
    {
        config([
            'services.paystack.default_currency' => 'GHS',
            'services.paystack.supported_currencies' => ['GHS'],
        ]);

        $paystack = app(PaystackService::class);

        $this->assertSame('GHS', $paystack->resolveChargeCurrency('USD'));
        $this->assertTrue($paystack->supportsCurrency('GHS'));
        $this->assertFalse($paystack->supportsCurrency('USD'));
    }

    public function test_keeps_usd_when_the_merchant_supports_it(): void
    {
        config([
            'services.paystack.default_currency' => 'GHS',
            'services.paystack.supported_currencies' => ['GHS', 'USD'],
        ]);

        $paystack = app(PaystackService::class);

        $this->assertSame('USD', $paystack->resolveChargeCurrency('USD'));
    }

    public function test_falls_back_to_ghs_amount_when_usd_is_not_supported(): void
    {
        config([
            'services.paystack.default_currency' => 'GHS',
            'services.paystack.supported_currencies' => ['GHS'],
        ]);

        $tour = new Tour([
            'tour_slug' => 'cape-coast-castles',
            'price_currency' => 'USD',
            'price_amount' => 200,
            'price_amount_usd' => 200,
            'price_amount_ghs' => 3000,
            'booking_settings' => ['depositPercent' => 100],
        ]);

        $service = new BookingServiceChargeProbe(
            app(PaystackService::class),
            app(BookingNotificationService::class),
        );

        $this->assertSame(
            ['amount' => 3000.0, 'currency' => 'GHS'],
            $service->publicResolvePaystackCharge($tour, 1, 200, 'USD')
        );
    }

    public function test_preserves_deposit_when_falling_back_to_ghs(): void
    {
        config([
            'services.paystack.default_currency' => 'GHS',
            'services.paystack.supported_currencies' => ['GHS'],
        ]);

        $tour = new Tour([
            'tour_slug' => 'cape-coast-castles',
            'price_currency' => 'USD',
            'price_amount' => 200,
            'price_amount_usd' => 200,
            'price_amount_ghs' => 3000,
            'booking_settings' => ['depositPercent' => 50],
        ]);

        $service = new BookingServiceChargeProbe(
            app(PaystackService::class),
            app(BookingNotificationService::class),
        );

        $this->assertSame(
            ['amount' => 1500.0, 'currency' => 'GHS'],
            $service->publicResolvePaystackCharge($tour, 1, 100, 'USD')
        );
    }
}
