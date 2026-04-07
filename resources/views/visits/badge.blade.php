<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Visitor Badge</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; }
        .badge { width: 100%; border: 2px solid #0d9488; border-radius: 12px; overflow: hidden; }
        .badge-header { background: linear-gradient(135deg, #0d9488, #0f766e); color: #fff; padding: 12px 16px; text-align: center; }
        .badge-header h1 { font-size: 14px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 2px; }
        .badge-header p { font-size: 9px; opacity: 0.8; }
        .badge-body { padding: 16px; text-align: center; }
        .visitor-name { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
        .visitor-org { font-size: 11px; color: #64748b; margin-bottom: 12px; }
        .qr-box { display: inline-block; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; }
        .info-grid { text-align: left; font-size: 10px; }
        .info-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #f1f5f9; }
        .info-label { color: #64748b; }
        .info-value { color: #0f172a; font-weight: 600; }
        .badge-type { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; margin-top: 8px;
            background: {{ $visitRequest->category === 'vip' ? '#fef3c7' : '#dbeafe' }};
            color: {{ $visitRequest->category === 'vip' ? '#92400e' : '#1e40af' }};
        }
        .badge-footer { background: #f1f5f9; padding: 8px; text-align: center; font-size: 8px; color: #64748b; }
        .valid-until { font-weight: 700; color: #dc2626; }
    </style>
</head>
<body>
    <div class="badge">
        <div class="badge-header">
            <h1>Visitor Pass</h1>
            <p>Ethio Telecom — Visitor Management System</p>
        </div>
        <div class="badge-body">
            <div class="visitor-name">{{ $visitRequest->visitor->full_name }}</div>
            <div class="visitor-org">{{ $visitRequest->visitor->organization ?: 'Individual' }}</div>

            @if($qrSvg)
            <div class="qr-box">
                {!! $qrSvg !!}
            </div>
            @endif

            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Badge #</span>
                    <span class="info-value">VB-{{ str_pad($visitRequest->id, 4, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Host</span>
                    <span class="info-value">{{ $visitRequest->host->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Site</span>
                    <span class="info-value">{{ $visitRequest->site->name }}</span>
                </div>
                @if($visitRequest->zone)
                <div class="info-row">
                    <span class="info-label">Zone</span>
                    <span class="info-value">{{ $visitRequest->zone->name }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Purpose</span>
                    <span class="info-value">{{ Str::limit($visitRequest->purpose, 30) }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date</span>
                    <span class="info-value">{{ $visitRequest->scheduled_at->format('M d, Y') }}</span>
                </div>
            </div>

            <div class="badge-type">{{ strtoupper($visitRequest->category) }}</div>
        </div>
        <div class="badge-footer">
            Valid for: <span class="valid-until">{{ $visitRequest->scheduled_at->format('M d, Y') }}</span> only.
            Must be worn visibly at all times. Return at reception upon departure.
        </div>
    </div>
</body>
</html>
