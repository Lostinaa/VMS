<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>VMS Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; color: #1e293b; }
        .header { background: linear-gradient(135deg, #0d9488, #0f766e); color: #fff; padding: 20px 30px; }
        .header h1 { font-size: 20px; margin-bottom: 4px; }
        .header p { font-size: 11px; opacity: 0.85; }
        .meta { padding: 15px 30px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; }
        .meta span { margin-right: 30px; font-size: 10px; color: #64748b; }
        .meta strong { color: #0f172a; }
        .stats { padding: 15px 30px; display: flex; gap: 12px; flex-wrap: wrap; }
        .stat { flex: 1; min-width: 100px; background: #f1f5f9; border-radius: 8px; padding: 10px; text-align: center; }
        .stat .value { font-size: 22px; font-weight: 700; color: #0f172a; }
        .stat .label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 30px; }
        table { width: calc(100% - 60px); margin: 15px 30px; }
        th { background: #0d9488; color: #fff; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 7px 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; }
        tr:nth-child(even) { background: #f8fafc; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 600; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fecaca; color: #991b1b; }
        .status-checked_in { background: #dbeafe; color: #1e40af; }
        .status-checked_out { background: #e0e7ff; color: #3730a3; }
        .footer { padding: 15px 30px; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Visitor Management Report</h1>
        <p>Ethio Telecom — Generated {{ now()->format('M d, Y H:i') }}</p>
    </div>

    <div class="meta">
        <span>Period: <strong>{{ $dateFrom }}</strong> to <strong>{{ $dateTo }}</strong></span>
    </div>

    <div class="stats">
        <div class="stat"><div class="value">{{ $stats['total_visits'] }}</div><div class="label">Total Visits</div></div>
        <div class="stat"><div class="value">{{ $stats['unique_visitors'] }}</div><div class="label">Unique Visitors</div></div>
        <div class="stat"><div class="value">{{ $stats['approved'] }}</div><div class="label">Approved</div></div>
        <div class="stat"><div class="value">{{ $stats['checked_in'] }}</div><div class="label">Checked In</div></div>
        <div class="stat"><div class="value">{{ $stats['checked_out'] }}</div><div class="label">Checked Out</div></div>
        <div class="stat"><div class="value">{{ $stats['rejected'] }}</div><div class="label">Rejected</div></div>
        <div class="stat"><div class="value">{{ $stats['pending'] }}</div><div class="label">Pending</div></div>
        <div class="stat"><div class="value">{{ $stats['avg_daily'] }}</div><div class="label">Avg/Day</div></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Visitor</th>
                <th>Organization</th>
                <th>Host</th>
                <th>Site</th>
                <th>Purpose</th>
                <th>Category</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $visit)
            <tr>
                <td>{{ $visit->id }}</td>
                <td>{{ $visit->visitor->full_name ?? '-' }}</td>
                <td>{{ $visit->visitor->organization ?? '-' }}</td>
                <td>{{ $visit->host->name ?? '-' }}</td>
                <td>{{ $visit->site->name ?? '-' }}</td>
                <td>{{ Str::limit($visit->purpose, 25) }}</td>
                <td>{{ $visit->category }}</td>
                <td><span class="status status-{{ $visit->status }}">{{ $visit->status }}</span></td>
                <td>{{ $visit->scheduled_at?->format('M d, H:i') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        VMS — Visitor Management System | Ethio Telecom | Confidential
    </div>
</body>
</html>
