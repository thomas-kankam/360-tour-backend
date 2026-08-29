<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\InvoiceRequest;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ClientInvoiceRequestController extends Controller
{
    public function __construct(protected NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $client = $request->user();

        $query = InvoiceRequest::query()
            ->where('client_slug', $client->client_slug)
            ->latest();

        $paginator = self::paginateQuery($request, $query, 20);

        return self::paginatedApiResponse('Requests retrieved', $paginator, fn (InvoiceRequest $item) => $item->toRequestArray());
    }

    public function store(Request $request): JsonResponse
    {
        $client = $request->user();

        $data = $request->validate([
            'type' => 'required|in:invoice,quote',
            'message' => 'required|string|max:5000',
        ]);

        $item = InvoiceRequest::create([
            'request_uuid' => (string) Str::uuid(),
            'client_slug' => $client->client_slug,
            'type' => $data['type'],
            'message' => $data['message'],
            'status' => 'pending',
        ]);

        $clientName = trim($client->first_name . ' ' . ($client->last_name ?? '')) ?: $client->email;
        $typeLabel = $data['type'] === 'quote' ? 'quote' : 'invoice';
        $adminUrl = NotificationService::adminBaseUrl() . '/admin/invoice-requests';

        $this->notifications->notifyAllAdmins(
            type: $data['type'] === 'quote'
                ? UserNotification::TYPE_QUOTE_REQUEST
                : UserNotification::TYPE_INVOICE_REQUEST,
            title: ucfirst($typeLabel) . ' request from ' . $clientName,
            body: $data['message'],
            actionUrl: $adminUrl,
            meta: ['request_uuid' => $item->request_uuid, 'client_slug' => $client->client_slug],
        );

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, ucfirst($typeLabel) . ' request submitted', $item->toRequestArray());
    }
}
