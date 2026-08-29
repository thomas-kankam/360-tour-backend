<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #111111; background: #FAF8F2; padding: 24px;">
    <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 28px; border: 1px solid #e5e7eb;">
        <h1 style="margin: 0 0 12px; font-size: 22px; color: #006B3C;">{{ $title }}</h1>
        @if(!empty($body))
            <p style="margin: 0 0 20px; color: #4b5563;">{{ $body }}</p>
        @endif
        @if(!empty($actionUrl))
            <p style="margin: 24px 0 0;">
                <a href="{{ $actionUrl }}" style="display: inline-block; background: #006B3C; color: #ffffff; text-decoration: none; padding: 12px 20px; border-radius: 10px; font-weight: bold;">
                    View details
                </a>
            </p>
        @endif
        <p style="margin: 28px 0 0; font-size: 12px; color: #9ca3af;">360 Tours Ghana</p>
    </div>
</body>
</html>
