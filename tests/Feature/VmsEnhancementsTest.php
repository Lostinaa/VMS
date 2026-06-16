<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Site;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitRequest;
use App\Notifications\VisitorCheckedOutNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VmsEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_qr_code_view_is_accessible_without_auth(): void
    {
        $visitor = Visitor::create([
            'full_name' => 'Test Visitor',
            'email' => 'visitor@test.com',
            'phone' => '1234567890',
            'organization' => 'Test Org',
            'id_type' => 'national_id',
            'id_number' => 'NID-12345',
        ]);

        $site = Site::create([
            'name' => 'HQ Site',
            'code' => 'HQ',
            'address' => 'HQ Street',
            'city' => 'Addis Ababa',
        ]);

        $host = User::create([
            'name' => 'Host User',
            'email' => 'host@test.com',
            'password' => bcrypt('password'),
            'role' => 'host',
        ]);

        $visitRequest = VisitRequest::create([
            'visitor_id' => $visitor->id,
            'host_id' => $host->id,
            'site_id' => $site->id,
            'purpose' => 'Meeting',
            'status' => 'approved',
            'qr_code' => 'VMS-QR-TEST-12345',
            'scheduled_at' => now()->addDay(),
        ]);

        // Access the public QR route
        $response = $this->get(route('visit.qr.public', 'VMS-QR-TEST-12345'));

        $response->assertStatus(200);
        $response->assertSee('Test Visitor');
        $response->assertSee('HQ Site');
    }

    public function test_qr_check_in_controller_handles_json_qr_codes(): void
    {
        $visitor = Visitor::create([
            'full_name' => 'Test Visitor 2',
            'email' => 'visitor2@test.com',
            'phone' => '1234567891',
            'organization' => 'Test Org',
            'id_type' => 'national_id',
            'id_number' => 'NID-12346',
        ]);

        $site = Site::create([
            'name' => 'HQ Site 2',
            'code' => 'HQ2',
            'address' => 'HQ Street',
            'city' => 'Addis Ababa',
        ]);

        $host = User::create([
            'name' => 'Host User 2',
            'email' => 'host2@test.com',
            'password' => bcrypt('password'),
            'role' => 'host',
        ]);

        $visitRequest = VisitRequest::create([
            'visitor_id' => $visitor->id,
            'host_id' => $host->id,
            'site_id' => $site->id,
            'purpose' => 'Interview',
            'status' => 'approved',
            'qr_code' => 'VMS-QR-JSON-999',
            'scheduled_at' => now()->addDay(),
        ]);

        // Scanned QR code is a JSON string (simulating legacy mobile screens)
        $scannedJson = json_encode([
            'id' => $visitRequest->id,
            'qr' => 'VMS-QR-JSON-999',
        ]);

        // Attempt check-in lookup via API
        $response = $this->get(route('api.qr.lookup', ['qr_code' => $scannedJson]));

        $response->assertStatus(200);
        $response->assertJsonPath('data.visitor_name', 'Test Visitor 2');
    }

    public function test_visitor_checkout_triggers_notification_to_host(): void
    {
        Notification::fake();

        $visitor = Visitor::create([
            'full_name' => 'Test Visitor 3',
            'email' => 'visitor3@test.com',
            'phone' => '1234567892',
            'organization' => 'Test Org',
            'id_type' => 'national_id',
            'id_number' => 'NID-12347',
        ]);

        $site = Site::create([
            'name' => 'HQ Site 3',
            'code' => 'HQ3',
            'address' => 'HQ Street',
            'city' => 'Addis Ababa',
        ]);

        $host = User::create([
            'name' => 'Host User 3',
            'email' => 'host3@test.com',
            'phone' => '0912345678', // has phone for SMS
            'password' => bcrypt('password'),
            'role' => 'host',
        ]);

        $visitRequest = VisitRequest::create([
            'visitor_id' => $visitor->id,
            'host_id' => $host->id,
            'site_id' => $site->id,
            'purpose' => 'Consulting',
            'status' => 'checked_in',
            'qr_code' => 'VMS-QR-COUT-777',
            'scheduled_at' => now()->subHour(),
        ]);

        $checkIn = CheckIn::create([
            'visit_request_id' => $visitRequest->id,
            'visitor_id' => $visitor->id,
            'checked_in_at' => now()->subHour(),
        ]);

        // Call check-out via API
        $response = $this->post(route('api.qr.checkout', ['qr_code' => 'VMS-QR-COUT-777']));

        $response->assertStatus(200);
        $this->assertEquals('checked_out', $visitRequest->fresh()->status);
        $this->assertNotNull($checkIn->fresh()->checked_out_at);

        Notification::assertSentTo(
            $host,
            VisitorCheckedOutNotification::class,
            function ($notification) use ($visitRequest) {
                return $notification->visitRequest->id === $visitRequest->id;
            }
        );
    }

    public function test_public_visit_request_for_normal_visitor_shows_pending_message(): void
    {
        $site = Site::create(['name' => 'HQ Site', 'code' => 'HQ', 'address' => 'HQ Street', 'city' => 'Addis Ababa']);
        $host = User::create(['name' => 'Host User', 'email' => 'host@test.com', 'password' => bcrypt('password'), 'role' => 'host']);

        \Livewire\Livewire::test(\App\Livewire\PublicVisitRequest::class)
            ->set('full_name', 'Normal Visitor')
            ->set('email', 'normal@test.com')
            ->set('phone', '1234567890')
            ->set('organization', 'Normal Org')
            ->set('id_number', 'NID-999')
            ->set('host_id', $host->id)
            ->set('site_id', $site->id)
            ->set('purpose', 'General Meeting')
            ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('submit')
            ->assertSee('Visit Request Submitted!')
            ->assertSee('pending approval')
            ->assertDontSee('Visit Request Approved!');
    }

    public function test_public_visit_request_for_whitelisted_visitor_shows_approved_message(): void
    {
        $visitor = Visitor::create([
            'full_name' => 'Whitelisted Visitor',
            'email' => 'whitelisted@test.com',
            'phone' => '0912345678',
            'organization' => 'VIP Org',
            'id_type' => 'national_id',
            'id_number' => 'NID-777',
            'is_whitelisted' => true,
        ]);

        $site = Site::create(['name' => 'HQ Site', 'code' => 'HQ', 'address' => 'HQ Street', 'city' => 'Addis Ababa']);
        $host = User::create(['name' => 'Host User', 'email' => 'host@test.com', 'password' => bcrypt('password'), 'role' => 'host']);

        \Livewire\Livewire::test(\App\Livewire\PublicVisitRequest::class)
            ->set('full_name', 'Whitelisted Visitor')
            ->set('email', 'whitelisted@test.com')
            ->set('phone', '0912345678')
            ->set('organization', 'VIP Org')
            ->set('id_number', 'NID-777')
            ->set('host_id', $host->id)
            ->set('site_id', $site->id)
            ->set('purpose', 'VIP Meeting')
            ->set('scheduled_at', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('submit')
            ->assertSee('Visit Request Approved!')
            ->assertSee('automatically approved')
            ->assertDontSee('pending approval');
    }
}
