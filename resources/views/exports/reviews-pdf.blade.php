<!-- resources/views/exports/reviews-pdf.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reviews Report - Omniscient</title>
    <style>
        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', 'Arial', 'Helvetica', sans-serif;
            font-size: 10px;
            color: #1e293b;
            background: #ffffff;
            padding: 25px;
            line-height: 1.5;
        }

        /* ===== HEADER SECTION ===== */
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 30px 35px;
            border-radius: 12px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(56, 189, 248, 0.08);
            border-radius: 50%;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: 10%;
            width: 200px;
            height: 200px;
            background: rgba(56, 189, 248, 0.05);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .header-left {
            flex: 1;
        }

        .header-left h1 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
            color: #ffffff;
        }

        .header-left h1 span {
            color: #38bdf8;
        }

        .header-left .subtitle {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 300;
        }

        .header-right {
            text-align: right;
            min-width: 150px;
        }

        .header-right .date {
            font-size: 11px;
            color: #94a3b8;
        }

        .header-right .badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 14px;
            background: rgba(56, 189, 248, 0.2);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 20px;
            font-size: 10px;
            color: #38bdf8;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        /* ===== FILTERS SECTION ===== */
        .filters-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .filters-section .label {
            font-size: 10px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-right: 4px;
        }

        .filter-tag {
            display: inline-block;
            padding: 3px 12px;
            background: #e2e8f0;
            border-radius: 12px;
            font-size: 9px;
            color: #475569;
            font-weight: 500;
        }

        .filter-tag strong {
            color: #0f172a;
        }

        /* ===== STATISTICS SECTION ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            text-align: center;
            transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .stat-card .stat-icon {
            font-size: 18px;
            margin-bottom: 4px;
        }

        .stat-card .stat-value {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-card .stat-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 2px;
        }

        .stat-card.total .stat-value { color: #0f172a; }
        .stat-card.rating .stat-value { color: #f59e0b; }
        .stat-card.pending .stat-value { color: #eab308; }
        .stat-card.approved .stat-value { color: #22c55e; }
        .stat-card.rejected .stat-value { color: #ef4444; }

        /* ===== TABLE SECTION ===== */
        .table-container {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .table-title {
            padding: 12px 18px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-title h3 {
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }

        .table-title .count {
            font-size: 10px;
            color: #94a3b8;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f1f5f9;
        }

        th {
            padding: 10px 12px;
            text-align: left;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        td {
            padding: 9px 12px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9.5px;
            vertical-align: middle;
        }

        tbody tr {
            transition: background 0.15s;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        /* ===== RATING STARS ===== */
        .stars {
            color: #f59e0b;
            font-size: 11px;
            letter-spacing: 1px;
        }

        .stars .empty {
            color: #d1d5db;
        }

        .rating-number {
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
            margin-left: 2px;
        }

        /* ===== STATUS BADGES ===== */
        .status-badge {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-badge.pending {
            background: #fef9c3;
            color: #854d0e;
            border: 1px solid #fde68a;
        }

        .status-badge.approved {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .status-badge.rejected {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* ===== REVIEWER CELL ===== */
        .reviewer-cell {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .reviewer-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #38bdf8, #0284c7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .reviewer-avatar.anonymous {
            background: linear-gradient(135deg, #94a3b8, #64748b);
        }

        .reviewer-info {
            display: flex;
            flex-direction: column;
        }

        .reviewer-info .name {
            font-weight: 600;
            color: #0f172a;
            font-size: 10px;
        }

        .reviewer-info .email {
            font-size: 8.5px;
            color: #94a3b8;
        }

        /* ===== BUSINESS CELL ===== */
        .business-name {
            font-weight: 600;
            color: #0f172a;
            font-size: 10px;
        }

        /* ===== CONTENT TRUNCATE ===== */
        .content-truncate {
            display: inline-block;
            max-width: 160px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #475569;
        }

        .reply-text {
            color: #2563eb;
            font-style: italic;
            font-size: 9px;
        }

        /* ===== DATE CELL ===== */
        .date-cell {
            font-size: 9px;
            color: #64748b;
            white-space: nowrap;
        }

        /* ===== FOOTER ===== */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            margin-top: 5px;
        }

        .footer .brand {
            font-size: 10px;
            font-weight: 600;
            color: #0f172a;
        }

        .footer .brand span {
            color: #0284c7;
        }

        .footer .info {
            font-size: 8.5px;
            color: #94a3b8;
        }

        /* ===== WATERMARK ===== */
        .watermark {
            position: fixed;
            bottom: 30px;
            right: 30px;
            opacity: 0.03;
            font-size: 60px;
            font-weight: 900;
            color: #0284c7;
            transform: rotate(-15deg);
            pointer-events: none;
            z-index: 0;
        }

        /* ===== RESPONSIVE HELPERS ===== */
        .text-center { text-align: center; }
        .text-muted { color: #94a3b8; }
        .fw-bold { font-weight: 700; }

        @page {
            margin: 15px 20px;
        }
    </style>
</head>
<body>

    <!-- Watermark -->
    <div class="watermark">OMNISCIENT</div>

    <!-- ===== HEADER ===== -->
    <div class="header">
        <div class="header-content">
            <div class="header-left">
                <h1>📋 Reviews <span>Report</span></h1>
                <div class="subtitle">Comprehensive review analytics and details</div>
            </div>
            <div class="header-right">
                <div class="date">Generated: {{ $exportDate }}</div>
                <div class="badge">▼ {{ $totalReviews }} Reviews</div>
            </div>
        </div>
    </div>

    <!-- ===== FILTERS ===== -->
    @if($filters['search'] || $filters['status'] || $filters['rating'] || $filters['date'])
        <div class="filters-section">
            <span class="label">🔍 Filters Applied:</span>
            @if($filters['search'])
                <span class="filter-tag"><strong>Search:</strong> {{ $filters['search'] }}</span>
            @endif
            @if($filters['status'])
                <span class="filter-tag"><strong>Status:</strong> {{ ucfirst($filters['status']) }}</span>
            @endif
            @if($filters['rating'])
                <span class="filter-tag"><strong>Rating:</strong> {{ $filters['rating'] }} ★</span>
            @endif
            @if($filters['date'])
                <span class="filter-tag"><strong>Date:</strong> {{ $filters['date'] }}</span>
            @endif
        </div>
    @endif

   

    <!-- ===== TABLE ===== -->
    <div class="table-container">
        <div class="table-title">
            <h3>📄 Review Details</h3>
            <span class="count">{{ $totalReviews }} record(s)</span>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 35px;">#</th>
                    <th style="width: 140px;">Reviewer</th>
                    <th style="width: 130px;">Business</th>
                    <th style="width: 65px;">Rating</th>
                    <th style="width: 180px;">Review</th>
                    <th style="width: 120px;">Reply</th>
                    <th style="width: 75px;">Status</th>
                    <th style="width: 100px;">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $index => $review)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="reviewer-cell">
                                <div class="reviewer-avatar {{ $review->user?->name ? '' : 'anonymous' }}">
                                    {{ $review->user?->name ? substr($review->user->name, 0, 2) : '??' }}
                                </div>
                                <div class="reviewer-info">
                                    <span class="name">{{ $review->user?->name ?? 'Anonymous' }}</span>
                                    <span class="email">{{ $review->user?->email ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="business-name">{{ $review->business?->name ?? 'Unknown' }}</div>
                        </td>
                        <td>
                            <div class="stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $review->rating ? '' : 'empty' }}">★</span>
                                @endfor
                            </div>
                            <div class="rating-number">{{ $review->rating }}/5</div>
                        </td>
                        <td>
                            <span class="content-truncate" title="{{ $review->content ?? 'No content' }}">
                                {{ $review->content ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="content-truncate reply-text" title="{{ $review->reply ?? 'No reply' }}">
                                {{ $review->reply ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="status-badge {{ $review->status }}">
                                {{ ucfirst($review->status) }}
                            </span>
                        </td>
                        <td class="date-cell">
                            {{ $review->created_at?->format('M d, Y H:i') ?? 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                            <div style="font-size: 12px; font-weight: 500;">No reviews found</div>
                            <div style="font-size: 10px; margin-top: 4px;">Try adjusting your filters</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- ===== FOOTER ===== -->
    <div class="footer">
        <div class="brand">🏢 <span>Omniscient</span> Directory</div>
        <div class="info">
            Generated automatically • {{ $totalReviews }} reviews • Confidential
        </div>
    </div>

</body>
</html>