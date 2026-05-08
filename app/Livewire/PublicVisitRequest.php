<?php

namespace App\Livewire;

use App\Models\Site;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitRequest;
use App\Models\Zone;
use App\Notifications\NewVisitRequestNotification;
use Livewire\Component;

class PublicVisitRequest extends Component
{
    // Visitor fields
    public string $full_name = '';
    public string $email = '';
    public string $phone = '';
    public string $organization = '';
    public string $id_type = 'national_id';
    public string $id_number = '';
    public string $car_plate_number = '';

    // Visit fields
    public ?int $host_id = null;
    public ?int $site_id = null;
    public ?int $zone_id = null;
    public string $purpose = '';
    public string $visitor_type = 'external';
    public string $category = 'general';
    public ?string $scheduled_at = null;
    public string $notes = '';

    // State
    public bool $submitted = false;
    public ?string $referenceCode = null;

    protected function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'organization' => 'required|string|max:255',
            'id_type' => 'required|in:national_id,passport,drivers_license,employee_id',
            'id_number' => 'required|string|max:50',
            'car_plate_number' => 'nullable|string|max:20',
            'host_id' => 'required|exists:users,id',
            'site_id' => 'required|exists:sites,id',
            'zone_id' => 'nullable|exists:zones,id',
            'purpose' => 'required|string|max:500',
            'visitor_type' => 'required|in:external,internal',
            'category' => 'required|in:general,contractor,vendor,vip,job_applicant,other',
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string|max:500',
        ];
    }

    protected $messages = [
        'full_name.required' => 'Please enter your full name.',
        'email.required' => 'Please enter your email address.',
        'phone.required' => 'Please enter your phone number.',
        'organization.required' => 'Please enter your organization name.',
        'id_number.required' => 'Please enter your ID number.',
        'host_id.required' => 'Please select who you are visiting.',
        'site_id.required' => 'Please select the site you wish to visit.',
        'purpose.required' => 'Please describe the purpose of your visit.',
        'scheduled_at.required' => 'Please select your preferred visit date and time.',
        'scheduled_at.after' => 'Visit date must be in the future.',
    ];

    public function updatedSiteId($value): void
    {
        $this->zone_id = null;
    }

    public function getZonesProperty()
    {
        if (!$this->site_id) return collect();
        return Zone::where('site_id', $this->site_id)->pluck('name', 'id');
    }

    public function submit(): void
    {
        $this->validate();

        // Find or create visitor
        $visitor = Visitor::firstOrCreate(
            ['email' => $this->email],
            [
                'full_name' => $this->full_name,
                'phone' => $this->phone,
                'organization' => $this->organization,
                'id_type' => $this->id_type,
                'id_number' => $this->id_number,
                'car_plate_number' => $this->car_plate_number ?: null,
            ]
        );

        // Check blacklist
        if ($visitor->is_blacklisted) {
            $this->addError('email', 'Unable to process your request. Please contact the front desk.');
            return;
        }

        // Determine initial status: auto-approve for whitelisted (FR-008) or internal visitors (FR-002)
        $autoApprove = false;
        $requiresSupervisor = false;

        // Whitelisted visitor with valid expiry
        if ($visitor->is_whitelisted) {
            if (!$visitor->whitelist_expires_at || $visitor->whitelist_expires_at >= now()) {
                $autoApprove = true;
            }
        }

        // FR-002: Internal visitor workflow
        if ($this->visitor_type === 'internal' && $this->zone_id) {
            $zone = \App\Models\Zone::find($this->zone_id);
            if ($zone && in_array($zone->security_level, ['restricted', 'high_security'])) {
                // Restricted zones require supervisor approval for internal visitors
                $host = User::find($this->host_id);
                if ($host && $host->supervisor_id) {
                    $requiresSupervisor = true;
                    $autoApprove = false;
                }
                // If no supervisor assigned, falls through to normal pending workflow
            } else {
                // Non-restricted zones: auto-approve internal visitors
                $autoApprove = true;
            }
        }

        $status = $autoApprove ? 'approved' : 'pending';

        // Create visit request
        $visit = VisitRequest::create([
            'visitor_id' => $visitor->id,
            'host_id' => $this->host_id,
            'site_id' => $this->site_id,
            'zone_id' => $this->zone_id,
            'purpose' => $this->purpose,
            'visitor_type' => $this->visitor_type,
            'category' => $this->category,
            'status' => $status,
            'scheduled_at' => $this->scheduled_at,
            'notes' => $requiresSupervisor
                ? ($this->notes ? $this->notes . ' | ' : '') . '[Requires supervisor approval — restricted zone]'
                : $this->notes,
        ]);

        // Generate QR code for approved visits
        if ($autoApprove) {
            $qrCode = 'VMS-QR-' . str_pad($visit->id, 6, '0', STR_PAD_LEFT) . '-' . \Illuminate\Support\Str::random(8);
            $visit->update(['qr_code' => $qrCode]);

            // Send approval notification to visitor
            $visit->load(['visitor', 'host', 'site', 'zone']);
            if ($visitor->email) {
                $visitor->notify(new \App\Notifications\VisitApprovedNotification($visit));
            }
        }

        // FR-002: Notify supervisor for restricted zone internal visits
        if ($requiresSupervisor) {
            $host = User::find($this->host_id);
            $supervisor = $host?->supervisor;
            if ($supervisor) {
                $visit->load(['visitor', 'host', 'site', 'zone']);
                $supervisor->notify(new NewVisitRequestNotification($visit));
            }
        }

        $this->referenceCode = 'VMS-' . str_pad($visit->id, 5, '0', STR_PAD_LEFT);
        $this->submitted = true;

        // Notify host about the new visit request (FR-007)
        $host = User::find($this->host_id);
        if ($host) {
            $visit->load(['visitor', 'host', 'site', 'zone']);
            $host->notify(new NewVisitRequestNotification($visit));
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'full_name', 'email', 'phone', 'organization',
            'id_type', 'id_number', 'car_plate_number',
            'host_id', 'site_id', 'zone_id', 'purpose',
            'visitor_type', 'category', 'scheduled_at', 'notes',
            'submitted', 'referenceCode',
        ]);
        $this->id_type = 'national_id';
        $this->visitor_type = 'external';
        $this->category = 'general';
    }

    public function render()
    {
        return view('livewire.public-visit-request', [
            'hosts' => User::where('role', 'host')->where('is_active', true)->pluck('name', 'id'),
            'sites' => Site::where('is_active', true)->pluck('name', 'id'),
        ])->layout('layouts.public');
    }
}
