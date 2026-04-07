<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - Visit #{{ $visitRequest->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: linear-gradient(135deg, #0f172a, #1e293b); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; border-radius: 20px; padding: 40px; max-width: 420px; width: 90%; box-shadow: 0 25px 60px rgba(0,0,0,0.3); text-align: center; }
        .logo { font-size: 14px; color: #64748b; text-transform: uppercase; letter-spacing: 3px; margin-bottom: 8px; }
        h1 { font-size: 22px; color: #0f172a; margin-bottom: 4px; }
        .visit-id { font-size: 13px; color: #94a3b8; margin-bottom: 24px; }
        .qr-container { background: #f8fafc; border-radius: 16px; padding: 24px; display: inline-block; margin-bottom: 24px; border: 2px dashed #e2e8f0; }
        .qr-container svg { display: block; }
        .details { text-align: left; border-top: 1px solid #e2e8f0; padding-top: 20px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f1f5f9; }
        .detail-label { color: #64748b; font-size: 13px; }
        .detail-value { color: #0f172a; font-size: 13px; font-weight: 600; }
        .status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;
            background: {{ $visitRequest->status === 'approved' ? '#dcfce7' : '#dbeafe' }};
            color: {{ $visitRequest->status === 'approved' ? '#166534' : '#1e40af' }};
        }
        .footer { margin-top: 20px; font-size: 11px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">Visitor Management System</div>
        <h1>{{ $visitRequest->visitor->full_name }}</h1>
        <div class="visit-id">Visit Request #{{ $visitRequest->id }}</div>

        <div class="qr-container">
            {!! $qrSvg !!}
        </div>

        <div class="details">
            <div class="detail-row">
                <span class="detail-label">Host</span>
                <span class="detail-value">{{ $visitRequest->host->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Site</span>
                <span class="detail-value">{{ $visitRequest->site->name }}</span>
            </div>
            @if($visitRequest->zone)
            <div class="detail-row">
                <span class="detail-label">Zone</span>
                <span class="detail-value">{{ $visitRequest->zone->name }}</span>
            </div>
            @endif
            <div class="detail-row">
                <span class="detail-label">Purpose</span>
                <span class="detail-value">{{ $visitRequest->purpose }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Scheduled</span>
                <span class="detail-value">{{ $visitRequest->scheduled_at->format('M d, Y H:i') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Status</span>
                <span class="status">{{ $visitRequest->status }}</span>
            </div>
        </div>

        <div class="footer">Present this QR code at reception for check-in</div>
    </div>
</body>
</html>
