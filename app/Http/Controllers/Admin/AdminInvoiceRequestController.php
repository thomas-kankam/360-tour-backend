<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceRequest;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInvoiceRequestController extends Controller
{
    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $query = InvoiceRequest::query()->with('client')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $paginator = self::paginateQuery($request, $query, 20);

        return self::paginatedApiResponse('Requests retrieved', $paginator, fn (InvoiceRequest $item) => $item->toRequestArray(includeClient: true));
    }

    public function show(InvoiceRequest $invoiceRequest): JsonResponse
    {
        $invoiceRequest->load('client');

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Request retrieved', $invoiceRequest->toRequestArray(includeClient: true));
    }

    public function respond(Request $request, InvoiceRequest $invoiceRequest): JsonResponse
    {
        $admin = $request->user();

        $data = $request->validate([
            'admin_response' => 'required|string|max:5000',
            'invoice_uuid' => 'nullable|string|exists:invoices,invoice_uuid',
            'status' => 'nullable|in:responded,closed',
        ]);

        $invoiceRequest->update([
            'admin_response' => $data['admin_response'],
            'invoice_uuid' => $data['invoice_uuid'] ?? $invoiceRequest->invoice_uuid,
            'admin_slug' => $admin->admin_slug,
            'status' => $data['status'] ?? 'responded',
        ]);

        $clientUrl = NotificationService::clientBaseUrl();
        $actionUrl = $data['invoice_uuid']
            ? $clientUrl . '/my-invoices/' . $data['invoice_uuid']
            : $clientUrl . '/my-invoices/requests';

        $this->notifications->notifyClient(
            clientSlug: $invoiceRequest->client_slug,
            type: $invoiceRequest->type === 'quote'
                ? UserNotification::TYPE_QUOTE_REQUEST_RESPONSE
                : UserNotification::TYPE_INVOICE_REQUEST_RESPONSE,
            title: $invoiceRequest->type === 'quote' ? 'Your quote request was answered' : 'Your invoice request was answered',
            body: $data['admin_response'],
            actionUrl: $actionUrl,
            meta: [
                'request_uuid' => $invoiceRequest->request_uuid,
                'invoice_uuid' => $data['invoice_uuid'] ?? null,
            ],
        );

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Response sent to client', $invoiceRequest->fresh()->toRequestArray(includeClient: true));
    }
}
