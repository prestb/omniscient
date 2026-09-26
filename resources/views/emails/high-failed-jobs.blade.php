<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>High Failed Jobs Alert</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; background: #f3f4f6; margin: 0; padding: 24px; color: #111827;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
        <!-- Header -->
        <tr>
            <td style="background: #dc2626; padding: 20px 24px;">
                <h1 style="margin: 0; font-size: 18px; font-weight: 700; color: #ffffff;">
                    ⚠️ High Failed Jobs Alert
                </h1>
                <p style="margin: 4px 0 0; font-size: 13px; color: #fecaca;">
                    Omniscient queue monitor
                </p>
            </td>
        </tr>

        <!-- Summary -->
        <tr>
            <td style="padding: 24px;">
                <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.5;">
                    <strong>{{ $totalFailed }}</strong> queue jobs failed in the last
                    <strong>{{ $windowMinutes }} minutes</strong> — above the alert threshold of
                    <strong>{{ $threshold }}</strong>.
                </p>

                <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.5; color: #4b5563;">
                    This usually indicates a broken job (mail server down, external API failing, database error)
                    or an invalid payload. Check the details below and the failed jobs dashboard.
                </p>

                <!-- Failed jobs table -->
                <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Failed At</th>
                            <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Queue</th>
                            <th style="text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; border-bottom: 1px solid #e5e7eb;">Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentJobs as $job)
                            <tr>
                                <td style="padding: 10px 12px; font-size: 12px; border-bottom: 1px solid #f3f4f6; color: #374151; white-space: nowrap;">
                                    {{ $job['failed_at'] }}
                                </td>
                                <td style="padding: 10px 12px; font-size: 12px; border-bottom: 1px solid #f3f4f6; color: #374151;">
                                    <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 11px;">{{ $job['queue'] }}</code>
                                </td>
                                <td style="padding: 10px 12px; font-size: 12px; border-bottom: 1px solid #f3f4f6; color: #b91c1c; word-break: break-word;">
                                    {{ $job['exception_summary'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- CTA -->
                <p style="margin: 24px 0 0; text-align: center;">
                    <a href="{{ url('/admin/dashboard') }}"
                       style="display: inline-block; background: #dc2626; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                        Open Admin Dashboard
                    </a>
                </p>

                <p style="margin: 16px 0 0; font-size: 12px; color: #9ca3af; text-align: center;">
                    Run <code style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px;">php artisan queue:retry all</code>
                    to retry failed jobs after fixing the underlying issue.
                </p>
            </td>
        </tr>

        <!-- Footer -->
        <tr>
            <td style="background: #f9fafb; padding: 16px 24px; border-top: 1px solid #e5e7eb;">
                <p style="margin: 0; font-size: 11px; color: #9ca3af; text-align: center;">
                    Sent by Omniscient queue monitor · {{ now()->toDayDateTimeString() }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>