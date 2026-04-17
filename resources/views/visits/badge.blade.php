<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Visitor Badge — Ethio Telecom</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', Arial, sans-serif; }
        .badge { width: 100%; border: 2px solid #4CAF50; border-radius: 12px; overflow: hidden; }
        .badge-header {
            background: linear-gradient(135deg, #4CAF50, #1B7D3A);
            color: #fff; padding: 14px 16px; text-align: center;
            position: relative;
        }
        .badge-header-logo {
            position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            height: 28px; width: auto; opacity: 0.9;
        }
        .badge-header h1 { font-size: 14px; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 2px; }
        .badge-header p { font-size: 9px; opacity: 0.85; }
        .badge-body { padding: 16px; text-align: center; }

        /* Photo section */
        .visitor-photo {
            width: 72px; height: 72px; border-radius: 50%; border: 3px solid #4CAF50;
            object-fit: cover; margin: 0 auto 8px; display: block;
        }
        .visitor-photo-placeholder {
            width: 72px; height: 72px; border-radius: 50%; border: 3px solid #4CAF50;
            background: #e8f5e9; margin: 0 auto 8px; display: flex; align-items: center;
            justify-content: center; font-size: 24px; font-weight: 700; color: #1B7D3A;
        }

        .visitor-name { font-size: 18px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
        .visitor-org { font-size: 11px; color: #64748b; margin-bottom: 12px; }
        .qr-box { display: inline-block; padding: 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; }
        .info-grid { text-align: left; font-size: 10px; }
        .info-row { display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #f1f5f9; }
        .info-label { color: #64748b; }
        .info-value { color: #0f172a; font-weight: 600; }
        .badge-type { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; margin-top: 8px;
            background: {{ $visitRequest->category === 'vip' ? '#fef3c7' : ($visitRequest->visitor_type === 'internal' ? '#e8f5e9' : '#e3f2fd') }};
            color: {{ $visitRequest->category === 'vip' ? '#92400e' : ($visitRequest->visitor_type === 'internal' ? '#1B7D3A' : '#1565C0') }};
        }
        .escort-badge {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 9px; font-weight: 700; text-transform: uppercase; margin-top: 4px;
            background: #fef2f2; color: #991b1b;
        }
        .access-level {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 9px; font-weight: 700; text-transform: uppercase; margin-top: 4px;
            background: #e3f2fd; color: #0d47a1;
        }
        .badge-footer {
            background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
            padding: 8px; text-align: center; font-size: 8px; color: #1B7D3A;
        }
        .valid-until { font-weight: 700; color: #dc2626; }
    </style>
</head>
<body>
    <div class="badge">
        <div class="badge-header">
            <h1>{{ $visitRequest->visitor_type === 'internal' ? 'Staff Visitor Pass' : 'Visitor Pass' }}</h1>
            <p>Ethio Telecom — Visitor Management System</p>
        </div>
        <div class="badge-body">
            {{-- Visitor Photo (FR-006) --}}
            @if($visitRequest->visitor->photo)
                <img src="{{ storage_path('app/public/' . $visitRequest->visitor->photo) }}" class="visitor-photo" alt="Photo">
            @else
                <div class="visitor-photo-placeholder">
                    {{ strtoupper(substr($visitRequest->visitor->full_name, 0, 1)) }}
                </div>
            @endif

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
                @if($visitRequest->meeting_location)
                <div class="info-row">
                    <span class="info-label">Location</span>
                    <span class="info-value">{{ $visitRequest->meeting_location }}</span>
                </div>
                @endif
                @if($visitRequest->parking_number)
                <div class="info-row">
                    <span class="info-label">Parking</span>
                    <span class="info-value">{{ $visitRequest->parking_number }}</span>
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

            <div class="badge-type">{{ strtoupper(str_replace('_', ' ', $visitRequest->category)) }}</div>

            @if($visitRequest->zone?->security_level === 'restricted' || $visitRequest->zone?->security_level === 'high_security')
            <div class="access-level">{{ strtoupper($visitRequest->zone->security_level) }}</div>
            @endif

            @if($visitRequest->zone?->escort_required)
            <div class="escort-badge">⚠ Escort Required</div>
            @endif
        </div>
        <div class="badge-footer">
            Valid for: <span class="valid-until">{{ $visitRequest->scheduled_at->format('M d, Y') }}</span> only.
            Must be worn visibly at all times. Return at reception upon departure.
        </div>
    </div>
</body>
</html>
