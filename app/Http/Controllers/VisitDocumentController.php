<?php

namespace App\Http\Controllers;

use App\Models\VisitRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class VisitDocumentController extends Controller
{
    public function qr(VisitRequest $visitRequest)
    {
        $visitRequest->load(['visitor', 'host', 'site', 'zone']);

        // Standardize: QR code encodes raw qr_code string to ensure kiosk scanning consistency
        $qrSvg = QrCode::format('svg')->size(300)->generate($visitRequest->qr_code);

        return view('visits.qr', compact('visitRequest', 'qrSvg'));
    }

    public function publicQr(string $qrCode)
    {
        $visitRequest = VisitRequest::where('qr_code', $qrCode)
            ->with(['visitor', 'host', 'site', 'zone'])
            ->firstOrFail();

        $qrSvg = QrCode::format('svg')->size(300)->generate($visitRequest->qr_code);

        return view('visits.qr', compact('visitRequest', 'qrSvg'));
    }

    public function badge(VisitRequest $visitRequest)
    {
        $visitRequest->load(['visitor', 'host', 'site', 'zone']);

        $qrSvg = '';
        if ($visitRequest->qr_code) {
            $qrSvg = QrCode::format('svg')->size(150)->generate($visitRequest->qr_code);
        }

        $pdf = Pdf::loadView('visits.badge', compact('visitRequest', 'qrSvg'))
            ->setPaper([0, 0, 252, 360], 'portrait'); // Badge size ~3.5x5 inches

        return $pdf->stream("badge-{$visitRequest->id}.pdf");
    }
}
