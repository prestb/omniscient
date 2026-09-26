<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics — {{ $business->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.5;
            padding: 32px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #0284c7;
        }

        .header h1 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
        }

        .header .subtitle {
            font-size: 11px;
            color: #6b7280;
        }

        .header .brand {
            font-size: 10px;
            color: #9ca3af;
            text-align: right;
        }

        .brand strong {
            color: #0284c7;
            font-size: 12px;
        }

        .meta {
            display: flex;
            gap: 24px;
            margin-bottom: 20px;
            font-size: 10px;
            color: #6b7280;
        }

        .meta strong {
            color: #111827;
        }

        .summary {
            display: table;
            width: 100%;
            margin-bottom: 24px;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .summary-row {
            display: table-row;
        }

        .summary-cell {
            display: table-cell;
            width: 20%;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 12px;
            text-align: center;
        }

        .summary-cell .label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .summary-cell .value {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        table.data thead {
            background: #f3f4f6;
        }

        table.data th {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #374151;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        table.data td {
            padding: 7px 10px;
            border-bottom: 1px solid #f3f4f6;
            color: #374151;
            font-size: 10px;
        }

        table.data tbody tr:nth-child(even) {
            background: #fafafa;
        }

        table.data td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .empty {
            text-align: center;
            padding: 40px 0;
            color: #9ca3af;
            font-style: italic;
        }

        .footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
            font-size: 9px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h1>{{ $business->name }}</h1>
            <div class="subtitle">Analytics report</div>
        </div>
        <div class="brand">
            <strong>Omniscient</strong><br>
            Business Directory
        </div>
    </div>

    <div class="meta">
        <div><strong>Period:</strong> {{ $startDate->format('M j, Y') }} — {{ $endDate->format('M j, Y') }} ({{ $days }} days)</div>
        <div><strong>Generated:</strong> {{ $generatedAt->format('M j, Y g:i A') }}</div>
    </div>

    <!-- Summary -->
    <div class="summary">
        <div class="summary-row">
            <div class="summary-cell">
                <div class="label">Views</div>
                <div class="value">{{ number_format($summary['views']) }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Unique Visitors</div>
                <div class="value">{{ number_format($summary['unique_visitors']) }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Phone Clicks</div>
                <div class="value">{{ number_format($summary['phone_clicks']) }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">WhatsApp</div>
                <div class="value">{{ number_format($summary['whatsapp_clicks']) }}</div>
            </div>
            <div class="summary-cell">
                <div class="label">Website</div>
                <div class="value">{{ number_format($summary['website_clicks']) }}</div>
            </div>
        </div>
    </div>

    <!-- Daily table -->
    @if($rows->count() > 0)
        <table class="data">
            <thead>
                <tr>
                    <th>Date</th>
                    <th style="text-align: right;">Views</th>
                    <th style="text-align: right;">Unique Visitors</th>
                    <th style="text-align: right;">Phone Clicks</th>
                    <th style="text-align: right;">WhatsApp</th>
                    <th style="text-align: right;">Website</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td>{{ $row->date->format('M j, Y') }}</td>
                        <td class="num">{{ number_format($row->views ?? 0) }}</td>
                        <td class="num">{{ number_format($row->unique_visitors ?? 0) }}</td>
                        <td class="num">{{ number_format($row->phone_clicks ?? 0) }}</td>
                        <td class="num">{{ number_format($row->whatsapp_clicks ?? 0) }}</td>
                        <td class="num">{{ number_format($row->website_clicks ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">
            No analytics data available for this period.
        </div>
    @endif

    <div class="footer">
        Generated by Omniscient on {{ $generatedAt->format('F j, Y \a\t g:i A') }}
    </div>

</body>
</html>