<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\BookingService;
use App\Services\PaystackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected PaystackService $paystack,
        protected BookingService $bookingService
    ) {}

    public function callback(Request $request): RedirectResponse
    {
        $frontendUrl = rtrim(config('custom.urls.frontend_url', 'https://360toursghana.com'), '/');
        $reference = $request->query('reference') ?? $request->input('trxref');
        Log::info('Payment callback', ['reference' => $reference]);

        if (! $reference) {
            Log::info('Payment callback failed', ['request' => $request->all()]);

            return $this->redirectToPaymentResult($frontendUrl, success: false);
        }

        try {
            $data = $this->paystack->verifyTransaction($reference);
            $paymentStatus = ($data['status'] ?? '') === 'success' ? 'success' : 'failed';

            if ($paymentStatus === 'success') {
                $this->bookingService->markPaidByReference($reference, $data);
            } else {
                $this->bookingService->markFailedByReference($reference, $data);
            }

            return $this->redirectToPaymentResult($frontendUrl, success: $paymentStatus === 'success', reference: $reference);
        } catch (\Throwable $e) {
            Log::error('Payment callback error', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return $this->redirectToPaymentResult($frontendUrl, success: false, reference: $reference);
        }
    }

    public function verify(Request $request): JsonResponse
    {
        $reference = $request->query('ref') ?? $request->query('reference');

        if (! $reference) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_FAIL, 'Missing payment reference', []);
        }

        $payment = Payment::query()
            ->where('paystack_reference', $reference)
            ->with('booking.tour')
            ->first();

        if (! $payment) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Payment not found', []);
        }

        $booking = $payment->booking;
        $isSuccess = $payment->status === 'success';

        return self::apiResponse(
            ! $isSuccess,
            $isSuccess ? 'Action Successful' : 'Action Unsuccessful',
            (string) ($isSuccess ? self::API_SUCCESS : self::API_FAIL),
            $isSuccess ? 'Payment verified' : 'Payment failed',
            $payment->toPaymentArray(),
        );
    }

    protected function redirectToPaymentResult(string $frontendUrl, bool $success, ?string $reference = null): RedirectResponse
    {
        $path = $success ? '/payment/success' : '/payment/failure';
        $url = $frontendUrl . $path;

        if ($reference) {
            $url .= '?ref=' . urlencode($reference);
        }

        return redirect()->away($url);
    }

    public function webhook(Request $request): JsonResponse
    {
        $signature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        if (! $this->paystack->validateWebhookSignature($payload, $signature)) {
            return response()->json(['message' => 'Invalid signature'], 400);
        }

        $event = $request->input('event');
        $data = $request->input('data', []);

        if ($event === 'charge.success' && ! empty($data['reference'])) {
            $this->bookingService->markPaidByReference($data['reference'], $data);
        }

        if (in_array($event, ['charge.failed', 'payment.failed'], true) && ! empty($data['reference'])) {
            $this->bookingService->markFailedByReference($data['reference'], $data);
        }

        return response()->json(['message' => 'Webhook received']);
    }
}
