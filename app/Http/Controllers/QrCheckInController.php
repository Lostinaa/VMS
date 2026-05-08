<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\CheckIn;
use App\Models\CheckInDocument;
use App\Models\ScreeningQuestion;
use App\Models\ScreeningResponse;
use App\Models\VisitRequest;
use App\Models\VisitorLog;
use App\Notifications\VisitorCheckedInNotification;
use Illuminate\Http\Request;

class QrCheckInController extends Controller
{
    /**
     * FR-005: QR code scan check-in endpoint.
     * Validates the QR code and auto-creates a check-in record.
     * FR-001: Pre-screening questionnaires enforced.
     * FR-008: Escort policy enforcement for restricted zones.
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
            Alert::create([
                'type' => 'blacklist',
                'severity' => 'critical',
                'visit_request_id' => $visitRequest->id,
                'visitor_id' => $visitRequest->visitor_id,
                'message' => "Blacklisted visitor {$visitRequest->visitor->full_name} attempted QR check-in.",
            ]);

            return response()->json(['success' => false, 'message' => 'Check-in denied. Please contact security.'], 403);
        }

        // FR-008: Escort policy enforcement
        $escortRequired = $visitRequest->zone?->escort_required ?? false;
        $escortId = $request->input('escort_id');

        if ($escortRequired && !$escortId) {
            return response()->json([
                'success' => false,
                'message' => 'This zone requires an escort. Please select an escort to proceed.',
                'escort_required' => true,
            ], 422);
        }

        // FR-001: Validate screening responses
        $screeningResponses = $request->input('screening_responses', []);
        $visitorType = $visitRequest->visitor_type ?? 'external';
        $requiredQuestions = ScreeningQuestion::forVisitorType($visitorType)
            ->where('is_required', true);

        foreach ($requiredQuestions as $question) {
            $response = collect($screeningResponses)->firstWhere('question_id', $question->id);
            if (!$response || empty($response['response'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Please answer all required screening questions.",
                    'screening_required' => true,
                ], 422);
            }
        }

        // Create check-in record
        $checkIn = CheckIn::create([
            'visit_request_id' => $visitRequest->id,
            'visitor_id' => $visitRequest->visitor_id,
            'checked_in_at' => now(),
            'checked_in_via_qr' => true,
            'escort_id' => $escortId ?: null,
        ]);

        // Save screening responses (FR-001)
        foreach ($screeningResponses as $sr) {
            if (!isset($sr['question_id']) || !isset($sr['response'])) continue;

            $question = ScreeningQuestion::find($sr['question_id']);
            $flagged = false;

            if ($question && $question->flag_answer) {
                $flagged = strtolower(trim($sr['response'])) === strtolower(trim($question->flag_answer));
            }

            ScreeningResponse::create([
                'check_in_id' => $checkIn->id,
                'screening_question_id' => $sr['question_id'],
                'response' => $sr['response'],
                'flagged' => $flagged,
            ]);

            // Create alert for flagged responses
            if ($flagged) {
                Alert::create([
                    'type' => 'screening',
                    'severity' => 'high',
                    'visit_request_id' => $visitRequest->id,
                    'visitor_id' => $visitRequest->visitor_id,
                    'message' => "Screening flag: {$visitRequest->visitor->full_name} answered \"{$sr['response']}\" to \"{$question->question_text}\".",
                ]);
            }
        }

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

        // Save uploaded documents (FR-005)
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('checkins/documents/' . $checkIn->id, 'public');
                CheckInDocument::create([
                    'check_in_id' => $checkIn->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'document_type' => $request->input('document_type', 'other'),
                ]);
            }
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
                'escort_required' => $escortRequired,
                'escort_id' => $escortId,
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

    /**
     * FR-001: Return active screening questions for a visitor type.
     * Called by kiosk JS to dynamically render the questionnaire.
     */
    public function screeningQuestions(Request $request)
    {
        $type = $request->input('visitor_type', 'external');
        $questions = ScreeningQuestion::forVisitorType($type);

        return response()->json([
            'success' => true,
            'data' => $questions->map(fn ($q) => [
                'id' => $q->id,
                'question_text' => $q->question_text,
                'question_text_am' => $q->question_text_am,
                'type' => $q->type,
                'options' => $q->options,
                'is_required' => $q->is_required,
            ]),
        ]);
    }

    /**
     * FR-008: Return list of available escorts (staff) for a given site.
     */
    public function availableEscorts(Request $request)
    {
        $siteId = $request->input('site_id');

        $escorts = \App\Models\User::where('is_active', true)
            ->whereIn('role', ['host', 'security', 'receptionist'])
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->select('id', 'name', 'role')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $escorts,
        ]);
    }
}
