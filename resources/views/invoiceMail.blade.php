<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333; line-height: 1.5;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <h2 style="margin-bottom: 4px;">{{ $company['legal_name'] ?? '360 Tours Ghana' }}</h2>
                @if (!empty($company['tagline']))
                    <p style="margin-top: 0;">{{ $company['tagline'] }}</p>
                @endif
            </td>
            <td align="right">
                <h1 style="margin-bottom: 4px;">Invoice</h1>
                <p style="margin-top: 0;"><strong>{{ $invoice->invoice_number }}</strong></p>
            </td>
        </tr>
    </table>

    @if ($customMessage)
        <p>{{ $customMessage }}</p>
    @endif

    <p>
        <strong>Bill To:</strong><br>
        {{ $invoice->billed_to_name }}<br>
        {{ $invoice->billed_to_email }}<br>
        @if ($invoice->billed_to_phone) {{ $invoice->billed_to_phone }}<br> @endif
        @if ($invoice->billed_to_address) {{ $invoice->billed_to_address }} @endif
    </p>

    <p>
        <strong>Issue Date:</strong> {{ $invoice->issue_date?->format('Y-m-d') }}<br>
        @if ($invoice->due_date)
            <strong>Due Date:</strong> {{ $invoice->due_date->format('Y-m-d') }}<br>
        @endif
        @if ($invoice->project)
            <strong>Project:</strong> {{ $invoice->project }}<br>
        @endif
    </p>

    <table width="100%" cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; margin-top: 20px;">
        <thead>
            <tr style="background: #586D39; color: #fff;">
                <th align="left">Description</th>
                <th align="right">Qty</th>
                <th align="right">Rate</th>
                <th align="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->line_items ?? [] as $item)
                <tr>
                    <td>{{ $item['description'] ?? '' }}</td>
                    <td align="right">{{ $item['quantity'] ?? 0 }}</td>
                    <td align="right">{{ number_format((float) ($item['rate'] ?? 0), 2) }} {{ $invoice->currency }}</td>
                    <td align="right">{{ number_format(((float) ($item['quantity'] ?? 0)) * ((float) ($item['rate'] ?? 0)), 2) }} {{ $invoice->currency }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top: 20px;">
        <strong>Subtotal:</strong> {{ number_format($subtotal, 2) }} {{ $invoice->currency }}<br>
        <strong>Total:</strong> {{ number_format($total, 2) }} {{ $invoice->currency }}
    </p>

    @if ($invoice->notes)
        <p><strong>Notes:</strong><br>{{ $invoice->notes }}</p>
    @endif

    @if ($invoice->terms)
        <p><strong>Terms:</strong><br>{{ $invoice->terms }}</p>
    @endif

    @if ($invoice->payment_details)
        <p><strong>Payment Details:</strong><br>{{ $invoice->payment_details }}</p>
    @endif
</body>
</html>
