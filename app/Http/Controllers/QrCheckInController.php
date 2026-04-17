<?php

namespace App\Http\Controllers;

use App\Models\CheckIn;
use App\Models\VisitRequest;
use App\Models\VisitorLog;
use App\Notifications\VisitorCheckedInNotification;
use Illuminate\Http\Request;

class QrCheckInController extends Controller
{
    /**
     * FR-005: QR code scan check-in endpoint.
     * Validates the QR code and auto-creates a check-in record.
     */
    public function checkIn(Request $request)
    {
        $qrCode = $request->input('qr_code');

        if (!$qrCode) {
            return response()->json(['success' => false, 'message' => 'QR code is required.'], 422);
        }

        // Find the visit request by QR code
        $visitRequest = VisitRequest::where('qr_code', $qrCode)
            ->with(['visitor', 'host', 'site', 'zone'])
            ->first();

        if (!$visitRequest) {
            return response()->json(['success' => false, 'message' => 'Invalid QR code.'], 404);
        }

        // Validate status
        if ($visitRequest->status === 'checked_in') {
            return response()->json(['success' => false, 'message' => 'Visitor is already checked in.'], 409);
        }

        if ($visitRequest->status === 'expired') {
            return response()->json(['success' => false, 'message' => 'This visit request has expired.'], 410);
        }

        if ($visitRequest->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => "Visit request is '{$visitRequest->status}'. Only approved requests can be checked in.",
            ], 422);
        }

        // Check blacklist
        if ($visitRequest->visitor->is_blacklisted) {
            // Create alert for blacklisted visitor attempt
            \App\Models\Alert::create([
                'type' => 'blacklist',
                'severity' => 'critical',
                'visit_request_id' => $visitRequest->id,
                'visitor_id' => $visitRequest->visitor_id,
                'message' => "Blacklisted visitor {$visitRequest->visitor->full_name} attempted QR check-in.",
            ]);

            return response()->json(['success' => false, 'message' => 'Check-in denied. Please contact security.'], 403);
        }

        // Create check-in record
        $checkIn = CheckIn::create([
            'visit_request_id' => $visitRequest->id,
            'visitor_id' => $visitRequest->visitor_id,
            'checked_in_at' => now(),
            'checked_in_via_qr' => true,
        ]);

        // Save photo from kiosk camera (FR-005)
        if ($request->has('photo') && str_starts_with($request->input('photo'), 'data:image')) {
            $photoData = $request->input('photo');
            $photoData = preg_replace('/^data:image\/\w+;base64,/', '', $photoData);
            $photoPath = 'checkins/photos/' . $checkIn->id . '.jpg';
            \Storage::disk('public')->put($photoPath, base64_decode($photoData));
            $checkIn->update(['photo_path' => $photoPath]);

            // Also update visitor photo if they don't have one
            if (!$visitRequest->visitor->photo) {
                $visitorPhotoPath = 'visitors/photos/' . $visitRequest->visitor_id . '.jpg';
                \Storage::disk('public')->put($visitorPhotoPath, base64_decode($photoData));
                $visitRequest->visitor->update(['photo' => $visitorPhotoPath]);
            }
        }

        // Save signature from kiosk canvas (FR-005)
        if ($request->has('signature') && str_starts_with($request->input('signature'), 'data:image')) {
            $sigData = $request->input('signature');
            $sigData = preg_replace('/^data:image\/\w+;base64,/', '', $sigData);
            $sigPath = 'checkins/signatures/' . $checkIn->id . '.png';
            \Storage::disk('public')->put($sigPath, base64_decode($sigData));
            $checkIn->update(['signature_path' => $sigPath]);
        }

        // Update visit status
        $visitRequest->update(['status' => 'checked_in']);

        // Notify host (FR-007)
        if ($visitRequest->host) {
            $visitRequest->host->notify(new VisitorCheckedInNotification($visitRequest));
        }

        // Create visitor log entry (FR-010)
        if ($visitRequest->zone_id) {
            VisitorLog::create([
                'visitor_id' => $visitRequest->visitor_id,
                'check_in_id' => $checkIn->id,
                'zone_id' => $visitRequest->zone_id,
                'access_point' => 'QR Scan Entry',
                'action' => 'entry',
                'logged_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful.',
            'data' => [
                'visitor' => $visitRequest->visitor->full_name,
                'organization' => $visitRequest->visitor->organization,
                'host' => $visitRequest->host->name,
                'site' => $visitRequest->site->name,
                'zone' => $visitRequest->zone?->name,
                'purpose' => $visitRequest->purpose,
                'checked_in_at' => now()->format('M d, Y H:i'),
                'badge_number' => $checkIn->badge_number,
                'escort_required' => $visitRequest->zone?->escort_required ?? false,
            ],
        ]);
    }

    /**
     * FR-005: QR code scan check-out endpoint.
     */
    public function checkOut(Request $request)
    {
        $qrCode = $request->input('qr_code');

        if (!$qrCode) {
            return response()->json(['success' => false, 'message' => 'QR code is required.'], 422);
        }

        $visitRequest = VisitRequest::where('qr_code', $qrCode)
            ->where('status', 'checked_in')
            ->with(['visitor', 'host', 'site'])
            ->first();

        if (!$visitRequest) {
            return response()->json(['success' => false, 'message' => 'No active check-in found for this QR code.'], 404);
        }

        $checkIn = CheckIn::where('visit_request_id', $visitRequest->id)
            ->whereNull('checked_out_at')
            ->first();

        if ($checkIn) {
            $checkIn->update([
                'checked_out_at' => now(),
            ]);
        }

        $visitRequest->update(['status' => 'checked_out']);

        // Create exit log (FR-010)
        if ($checkIn && $visitRequest->zone_id) {
            VisitorLog::create([
                'visitor_id' => $visitRequest->visitor_id,
                'check_in_id' => $checkIn->id,
                'zone_id' => $visitRequest->zone_id,
                'access_point' => 'QR Scan Exit',
                'action' => 'exit',
                'logged_at' => now(),
            ]);
        }

        $duration = $checkIn ? $checkIn->checked_in_at->diffForHumans(now(), true) : 'unknown';

        return response()->json([
            'success' => true,
            'message' => 'Check-out successful.',
            'data' => [
                'visitor' => $visitRequest->visitor->full_name,
                'duration' => $duration,
                'checked_out_at' => now()->format('M d, Y H:i'),
            ],
        ]);
    }

    /**
     * Display QR scan page (kiosk-friendly).
     */
    public function scanPage()
    {
        return view('visits.qr-scan');
    }
}
