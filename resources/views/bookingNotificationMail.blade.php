<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $headline }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
</head>

<body style="margin: 0; padding: 0; background-color: #e6e6e6; font-family: 'Poppins', Arial, sans-serif;">
    <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#e6e6e6">
        <tr>
            <td align="center" style="padding: 40px 10px;">
                <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff"
                    style="max-width: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
                    <tr>
                        <td align="center" bgcolor="#586D39" style="padding: 30px 20px;">
                            <img src="https://360toursghana.com/images/logo.png" alt="360 Tours Ghana"
                                style="max-width: 160px; margin-bottom: 10px;">
                            <p style="margin: 0; font-size: 12px; letter-spacing: 2px; color: #f4f7ef; text-transform: uppercase;">
                                360 Tours Ghana
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px 25px; background-color: #fdfdfd;">
                            <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#ffffff"
                                style="border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); margin-bottom: 25px;">
                                <tr>
                                    <td style="padding: 20px 25px;">
                                        <p style="font-size: 16px; color: #333333; margin-bottom: 12px;">
                                            Hello
                                            <span
                                                style="display: inline-block; background-color: #f4f7ef; padding: 6px 10px; border-radius: 6px; color: #586D39; font-weight: 600; font-size: 16px; border: 1px solid #c8d4b8;">
                                                {{ $recipientName }}
                                            </span>,
                                        </p>
                                        <p style="font-size: 18px; color: #586D39; font-weight: 600; margin: 0 0 12px;">
                                            {{ $headline }}
                                        </p>
                                        <p style="font-size: 15px; color: #555555; line-height: 1.6; margin: 0 0 16px;">
                                            {{ $body }}
                                        </p>
                                        <p style="font-size: 14px; color: #555555; line-height: 1.6; margin: 0 0 8px;">
                                            <strong>Booking code:</strong> {{ $booking->booking_code }}
                                        </p>
                                        <p style="font-size: 14px; color: #555555; line-height: 1.6; margin: 0 0 8px;">
                                            <strong>Tour:</strong> {{ $booking->tour?->name ?? $booking->tour_slug }}
                                        </p>
                                        <p style="font-size: 14px; color: #555555; line-height: 1.6; margin: 0 0 8px;">
                                            <strong>Date:</strong> {{ $booking->selected_date?->format('M j, Y') }}
                                        </p>
                                        <p style="font-size: 14px; color: #555555; line-height: 1.6; margin: 0 0 16px;">
                                            <strong>Travelers:</strong> {{ $booking->travelers }}
                                        </p>
                                        @if ($actionUrl)
                                            <p style="margin: 0;">
                                                <a href="{{ $actionUrl }}"
                                                    style="display: inline-block; background-color: #586D39; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: 8px; font-weight: 600;">
                                                    View booking
                                                </a>
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
