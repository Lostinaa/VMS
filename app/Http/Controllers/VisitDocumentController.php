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

        $qrData = json_encode([
            'id' => $visitRequest->id,
            'visitor' => $visitRequest->visitor->full_name,
            'host' => $visitRequest->host->name,
            'site' => $visitRequest->site->name,
            'purpose' => $visitRequest->purpose,
            'qr' => $visitRequest->qr_code,
        ]);

        $qrSvg = QrCode::format('svg')->size(300)->generate($qrData);

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
