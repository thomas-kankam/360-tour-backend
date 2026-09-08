<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientInvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $client = $request->user();
        $email = strtolower(trim((string) ($client->email ?? '')));
        $slug = trim((string) ($client->client_slug ?? ''));

        // Never match on null/empty client_slug — Laravel's where(col, null) becomes
        // WHERE col IS NULL and would return other people's unlinked invoices.
        $query = Invoice::query()
            ->where(function ($builder) use ($email, $slug) {
                if ($slug !== '') {
                    $builder->where('client_slug', $slug);
                }

                if ($email !== '') {
                    $method = $slug !== '' ? 'orWhereRaw' : 'whereRaw';
                    $builder->{$method}('LOWER(TRIM(billed_to_email)) = ?', [$email]);
                }

                if ($slug === '' && $email === '') {
                    $builder->whereRaw('1 = 0');
                }
            })
            ->whereIn('status', ['sent', 'paid'])
            ->latest();

        $paginator = self::paginateQuery($request, $query, 20);

        return self::paginatedApiResponse('Invoices retrieved', $paginator, fn (Invoice $invoice) => $invoice->toInvoiceArray());
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $client = $request->user();

        if (! $this->clientCanView($client, $invoice)) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Invoice not found', []);
        }

        if (! in_array($invoice->status, ['sent', 'paid'], true)) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Invoice not found', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Invoice retrieved', $invoice->toInvoiceArray());
    }

    protected function clientCanView($client, Invoice $invoice): bool
    {
        $email = strtolower(trim((string) ($client->email ?? '')));
        $slug = trim((string) ($client->client_slug ?? ''));

        if ($slug !== '' && $invoice->client_slug && $invoice->client_slug === $slug) {
            return true;
        }

        if ($email === '') {
            return false;
        }

        return strcasecmp(trim((string) $invoice->billed_to_email), $email) === 0;
    }
}
