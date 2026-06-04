<?php

namespace App\Livewire;

use App\Models\Site;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitRequest;
use App\Models\Zone;
use App\Notifications\NewVisitRequestNotification;
use Livewire\Component;
use Illuminate\Support\Str;

class GroupVisitRequest extends Component
{
    // Common visit details
    public ?int $host_id = null;
    public ?int $site_id = null;
    public ?int $zone_id = null;
    public ?int $department_id = null;
    public string $purpose = '';
    public string $visitor_type = 'external';
    public string $category = 'general';
    public ?string $scheduled_at = null;
    public string $notes = '';

    // Group visitors (array of visitor data)
    public array $visitors = [
        ['full_name' => '', 'email' => '', 'phone' => '', 'organization' => '', 'id_type' => 'national_id', 'id_number' => ''],
    ];

    // State
    public bool $submitted = false;
    public ?string $groupId = null;
    public int $createdCount = 0;

    public function addVisitor(): void
    {
        $this->visitors[] = ['full_name' => '', 'email' => '', 'phone' => '', 'organization' => '', 'id_type' => 'national_id', 'id_number' => ''];
    }

    public function removeVisitor(int $index): void
    {
        if (count($this->visitors) > 1) {
            unset($this->visitors[$index]);
            $this->visitors = array_values($this->visitors);
        }
    }

    public function updatedSiteId($value): void
    {
        $this->zone_id = null;
    }

    protected function rules(): array
    {
        return [
            'host_id' => 'required|exists:users,id',
            'site_id' => 'required|exists:sites,id',
            'zone_id' => 'nullable|exists:zones,id',
            'department_id' => 'nullable|exists:departments,id',
            'purpose' => 'required|string|max:500',
            'visitor_type' => 'required|in:external,internal',
            'category' => 'required|in:general,contractor,vendor,vip,job_applicant,other',
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string|max:500',
            'visitors' => 'required|array|min:1',
            'visitors.*.full_name' => 'required|string|max:255',
            'visitors.*.email' => 'required|email|max:255',
            'visitors.*.phone' => 'required|string|max:20',
            'visitors.*.organization' => 'required|string|max:255',
            'visitors.*.id_type' => 'required|in:national_id,passport,drivers_license,employee_id',
            'visitors.*.id_number' => 'required|string|max:50',
        ];
    }

    protected $validationAttributes = [
        'visitors.*.full_name' => 'visitor name',
        'visitors.*.email' => 'visitor email',
        'visitors.*.phone' => 'visitor phone',
        'visitors.*.organization' => 'organization',
        'visitors.*.id_number' => 'ID number',
    ];

    public function submit(): void
    {
        $this->validate();

        $groupId = 'GRP-' . Str::upper(Str::random(8));
        $createdCount = 0;

        foreach ($this->visitors as $visitorData) {
            $visitor = Visitor::firstOrCreate(
                ['email' => $visitorData['email']],
                [
                    'full_name' => $visitorData['full_name'],
                    'phone' => $visitorData['phone'],
                    'organization' => $visitorData['organization'],
                    'id_type' => $visitorData['id_type'],
                    'id_number' => $visitorData['id_number'],
                ]
            );

            if ($visitor->is_blacklisted) {
                // Log a critical blacklist alert!
                \App\Models\Alert::create([
                    'type' => 'blacklist',
                    'severity' => 'critical',
                    'visitor_id' => $visitor->id,
                    'message' => "Blacklisted visitor {$visitor->full_name} was included in group visit request attempt.",
                ]);
                continue; // Skip blacklisted visitors silently
            }

            VisitRequest::create([
                'visitor_id' => $visitor->id,
                'host_id' => $this->host_id,
                'site_id' => $this->site_id,
                'zone_id' => $this->zone_id,
                'department_id' => $this->department_id,
                'purpose' => $this->purpose,
                'visitor_type' => $this->visitor_type,
                'category' => $this->category,
                'status' => 'pending',
                'scheduled_at' => $this->scheduled_at,
                'notes' => $this->notes,
                'group_id' => $groupId,
            ]);

            $createdCount++;
        }

        $this->groupId = $groupId;
        $this->createdCount = $createdCount;
        $this->submitted = true;

        // Notify host
        $host = User::find($this->host_id);
        if ($host) {
            $lastVisit = VisitRequest::where('group_id', $groupId)
                ->with(['visitor', 'host', 'site', 'zone'])
                ->first();
            if ($lastVisit) {
                $host->notify(new NewVisitRequestNotification($lastVisit));
            }
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'host_id', 'site_id', 'zone_id', 'department_id', 'purpose',
            'visitor_type', 'category', 'scheduled_at', 'notes',
            'submitted', 'groupId', 'createdCount',
        ]);
        $this->visitors = [
            ['full_name' => '', 'email' => '', 'phone' => '', 'organization' => '', 'id_type' => 'national_id', 'id_number' => ''],
        ];
    }

    public function render()
    {
        $zones = $this->site_id
            ? Zone::where('site_id', $this->site_id)->pluck('name', 'id')
            : collect();

        return view('livewire.group-visit-request', [
            'hosts' => User::where('role', 'host')->where('is_active', true)->pluck('name', 'id'),
            'sites' => Site::where('is_active', true)->pluck('name', 'id'),
            'departments' => \App\Models\Department::where('is_active', true)->pluck('name', 'id'),
            'zones' => $zones,
        ])->layout('layouts.public');
    }
}
