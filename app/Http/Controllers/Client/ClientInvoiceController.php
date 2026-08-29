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

        $query = Invoice::query()
            ->where(function ($builder) use ($client) {
                $builder->where('client_slug', $client->client_slug)
                    ->orWhere('billed_to_email', $client->email);
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
        if ($invoice->client_slug && $invoice->client_slug === $client->client_slug) {
            return true;
        }

        return strcasecmp((string) $invoice->billed_to_email, (string) $client->email) === 0;
    }
}
