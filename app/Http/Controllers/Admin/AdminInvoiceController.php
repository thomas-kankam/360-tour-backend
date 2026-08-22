<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendInvoiceEmailJob;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\InvoiceNumberService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminInvoiceController extends Controller
{
    public function __construct(protected InvoiceNumberService $invoiceNumberService) {}

    public function index(Request $request): JsonResponse
    {
        $query = Invoice::query()->latest();

        if ($request->filled('search')) {
            $term = '%' . trim((string) $request->search) . '%';
            $query->where(function ($builder) use ($term) {
                $builder->where('invoice_number', 'like', $term)
                    ->orWhere('billed_to_name', 'like', $term)
                    ->orWhere('billed_to_email', 'like', $term)
                    ->orWhere('project', 'like', $term)
                    ->orWhere('reference', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $paginator = self::paginateQuery($request, $query);

        return self::paginatedApiResponse('Invoices retrieved', $paginator, fn (Invoice $invoice) => $invoice->toInvoiceArray());
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Invoice retrieved', $invoice->toInvoiceArray());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedInvoicePayload($request);

        $invoice = Invoice::create(array_merge($data, [
            'invoice_uuid' => (string) Str::uuid(),
            'invoice_number' => $this->invoiceNumberService->generate(),
            'status' => $data['status'] ?? 'draft',
        ]));

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Invoice created', $invoice->toInvoiceArray());
    }

    public function generateNumber(): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Invoice number generated', [
            'invoice_number' => $this->invoiceNumberService->generate(),
        ]);
    }

    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $this->validatedInvoicePayload($request, partial: true);

        unset($data['invoice_number']);
        $invoice->update($data);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Invoice updated', $invoice->fresh()->toInvoiceArray());
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Invoice deleted', []);
    }

    public function send(Request $request, Invoice $invoice): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email',
            'attach_pdf' => 'nullable|boolean',
            'message' => 'nullable|string|max:5000',
        ]);

        SendInvoiceEmailJob::dispatch(
            $invoice->invoice_uuid,
            $data['email'],
            (bool) ($data['attach_pdf'] ?? false),
            $data['message'] ?? null,
        );

        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent']);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Invoice email queued', [
            'invoice_number' => $invoice->invoice_number,
            'email' => $data['email'],
        ]);
    }

    protected function validatedInvoicePayload(Request $request, bool $partial = false): array
    {
        $rules = [
            'status' => ($partial ? 'sometimes|' : '') . 'in:draft,sent,paid,cancelled',
            'issue_date' => ($partial ? 'sometimes|' : 'required|') . 'date',
            'due_date' => 'nullable|date',
            'reference' => 'nullable|string|max:255',
            'project' => 'nullable|string|max:255',
            'currency' => ($partial ? 'sometimes|' : 'required|') . 'string|max:10',
            'tax_percent' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0',
            'shipping' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'payment_details' => 'nullable|string',
            'billed_to_name' => ($partial ? 'sometimes|' : 'required|') . 'string|max:255',
            'billed_to_email' => ($partial ? 'sometimes|' : 'required|') . 'email|max:255',
            'billed_to_phone' => 'nullable|string|max:50',
            'billed_to_address' => 'nullable|string',
            'client_slug' => 'nullable|string|exists:clients,client_slug',
            'line_items' => ($partial ? 'sometimes|' : 'required|') . 'array|min:1',
            'line_items.*.description' => 'required_with:line_items|string|max:255',
            'line_items.*.quantity' => 'required_with:line_items|numeric|min:0',
            'line_items.*.rate' => 'required_with:line_items|numeric|min:0',
            'line_items.*.id' => 'nullable|string|max:255',
        ];

        $validated = $request->validate($rules);

        if (isset($validated['line_items'])) {
            $validated['line_items'] = collect($validated['line_items'])->map(function (array $item) {
                return [
                    'id' => $item['id'] ?? (string) Str::uuid(),
                    'description' => $item['description'],
                    'quantity' => (float) $item['quantity'],
                    'rate' => (float) $item['rate'],
                ];
            })->values()->all();
        }

        return $validated;
    }
}
