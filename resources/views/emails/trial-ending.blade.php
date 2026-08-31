<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Ending Soon</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 32px; }
        .icon { width: 64px; height: 64px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .icon svg { width: 32px; height: 32px; color: white; }
        h1 { color: #1f2937; font-size: 24px; margin: 0 0 8px 0; }
        p { color: #6b7280; line-height: 1.6; margin: 0 0 16px 0; }
        .highlight { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin: 24px 0; text-align: center; }
        .highlight strong { color: #166534; }
        .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 8px; font-weight: 600; margin: 16px 0; }
        .footer { text-align: center; margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb; }
        .footer p { font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1>Trial Ending Soon</h1>
            </div>

            <p>Hi {{ $user->name }},</p>

            <p>Your <strong>7-day free trial</strong> will end in <strong>3 days</strong>.</p>

            <div class="highlight">
                <p style="margin: 0;">
                    <strong>Trial ends:</strong> {{ \Carbon\Carbon::parse($trialEndsAt)->format('F j, Y') }}<br>
                    <strong>First charge:</strong> After trial ends
                </p>
            </div>

            <p>To continue using all features after your trial, no action is needed — your payment method will be charged automatically.</p>

            <p>If you'd like to cancel before being charged, you can do so from your dashboard.</p>

            <div style="text-align: center;">
                <a href="{{ route('dashboard') }}" class="button">Go to Dashboard</a>
            </div>

            <div class="footer">
                <p>This email was sent to {{ $user->email }}</p>
                <p>{{ config('app.name', 'Laravel') }} — Subscription Billing</p>
            </div>
        </div>
    </div>
</body>
</html>
