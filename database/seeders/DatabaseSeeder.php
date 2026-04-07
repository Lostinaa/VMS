<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\Badge;
use App\Models\CheckIn;
use App\Models\Department;
use App\Models\Site;
use App\Models\User;
use App\Models\VisitApproval;
use App\Models\VisitRequest;
use App\Models\Visitor;
use App\Models\VisitorLog;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Sites ──
        $hq = Site::create(['name' => 'Ethio Telecom HQ', 'code' => 'ET-HQ', 'address' => 'Churchill Ave, Addis Ababa', 'city' => 'Addis Ababa', 'timezone' => 'Africa/Addis_Ababa', 'is_active' => true]);
        $dc = Site::create(['name' => 'Data Center Akaki', 'code' => 'ET-DC', 'address' => 'Akaki-Kality, Addis Ababa', 'city' => 'Addis Ababa', 'timezone' => 'Africa/Addis_Ababa', 'is_active' => true]);
        $regional = Site::create(['name' => 'Adama Branch', 'code' => 'ET-ADM', 'address' => 'Main Street, Adama', 'city' => 'Adama', 'timezone' => 'Africa/Addis_Ababa', 'is_active' => true]);

        // ── Departments ──
        $itDept = Department::create(['name' => 'IT & Digital', 'site_id' => $hq->id]);
        $hrDept = Department::create(['name' => 'Human Resources', 'site_id' => $hq->id]);
        $finDept = Department::create(['name' => 'Finance', 'site_id' => $hq->id]);
        $secDept = Department::create(['name' => 'Security', 'site_id' => $hq->id]);
        $netDept = Department::create(['name' => 'Network Operations', 'site_id' => $dc->id]);
        Department::create(['name' => 'Customer Service', 'site_id' => $regional->id]);

        // ── Zones ──
        $lobbyZone = Zone::create(['name' => 'Main Lobby', 'site_id' => $hq->id, 'security_level' => 'normal', 'description' => 'Ground floor reception area', 'escort_required' => false, 'is_active' => true]);
        $officeZone = Zone::create(['name' => 'Office Floors (2-8)', 'site_id' => $hq->id, 'security_level' => 'normal', 'description' => 'Standard office space', 'escort_required' => false, 'is_active' => true]);
        $execZone = Zone::create(['name' => 'Executive Suite (9F)', 'site_id' => $hq->id, 'security_level' => 'restricted', 'description' => 'CXO offices', 'escort_required' => true, 'is_active' => true]);
        $serverRoom = Zone::create(['name' => 'Server Room', 'site_id' => $dc->id, 'security_level' => 'high_security', 'description' => 'Core data center', 'escort_required' => true, 'is_active' => true]);
        Zone::create(['name' => 'NOC', 'site_id' => $dc->id, 'security_level' => 'restricted', 'description' => 'Network Operations Center', 'escort_required' => true, 'is_active' => true]);

        // ── Users ──
        $admin = User::where('email', 'admin@vms.com')->first();
        if ($admin) {
            $admin->update(['site_id' => $hq->id, 'department_id' => $itDept->id]);
        }

        $receptionist = User::create(['name' => 'Sara Tadesse', 'email' => 'sara@ethiotelecom.et', 'password' => bcrypt('password'), 'phone' => '+251911223344', 'employee_id' => 'ET-1001', 'role' => 'receptionist', 'site_id' => $hq->id, 'department_id' => $secDept->id, 'is_active' => true]);
        $host1 = User::create(['name' => 'Dawit Kebede', 'email' => 'dawit@ethiotelecom.et', 'password' => bcrypt('password'), 'phone' => '+251922334455', 'employee_id' => 'ET-2001', 'role' => 'host', 'site_id' => $hq->id, 'department_id' => $itDept->id, 'is_active' => true]);
        $host2 = User::create(['name' => 'Hana Mekonnen', 'email' => 'hana@ethiotelecom.et', 'password' => bcrypt('password'), 'phone' => '+251933445566', 'employee_id' => 'ET-2002', 'role' => 'host', 'site_id' => $hq->id, 'department_id' => $hrDept->id, 'is_active' => true]);
        $security = User::create(['name' => 'Yonas Assefa', 'email' => 'yonas@ethiotelecom.et', 'password' => bcrypt('password'), 'phone' => '+251944556677', 'employee_id' => 'ET-3001', 'role' => 'security', 'site_id' => $hq->id, 'department_id' => $secDept->id, 'is_active' => true]);
        $cxoPa = User::create(['name' => 'Meron Girma', 'email' => 'meron@ethiotelecom.et', 'password' => bcrypt('password'), 'phone' => '+251955667788', 'employee_id' => 'ET-4001', 'role' => 'cxo_pa', 'site_id' => $hq->id, 'department_id' => $finDept->id, 'is_active' => true]);

        // ── Visitors ──
        $v1 = Visitor::create(['full_name' => 'Abebe Worku', 'email' => 'abebe@gmail.com', 'phone' => '+251911001122', 'organization' => 'Safaricom Ethiopia', 'id_type' => 'national_id', 'id_number' => 'ET-ID-90001']);
        $v2 = Visitor::create(['full_name' => 'Tigist Haile', 'email' => 'tigist@huawei.com', 'phone' => '+251922112233', 'organization' => 'Huawei Technologies', 'id_type' => 'passport', 'id_number' => 'CN-P-123456']);
        $v3 = Visitor::create(['full_name' => 'Michael Johnson', 'email' => 'mjohnson@ericsson.com', 'phone' => '+46701234567', 'organization' => 'Ericsson', 'id_type' => 'passport', 'id_number' => 'SE-P-789012']);
        $v4 = Visitor::create(['full_name' => 'Fatima Ahmed', 'email' => 'fatima@contractor.com', 'phone' => '+251933223344', 'organization' => 'TechBuild Contractors', 'id_type' => 'national_id', 'id_number' => 'ET-ID-90002']);
        $v5 = Visitor::create(['full_name' => 'Solomon Bekele', 'email' => 'solomon@jobseeker.com', 'phone' => '+251944334455', 'organization' => '', 'id_type' => 'national_id', 'id_number' => 'ET-ID-90003']);
        $v6 = Visitor::create(['full_name' => 'Chen Wei', 'email' => 'chen.wei@zte.com', 'phone' => '+861391234567', 'organization' => 'ZTE Corporation', 'id_type' => 'passport', 'id_number' => 'CN-P-654321', 'is_blacklisted' => true, 'blacklist_reason' => 'Unauthorized access to restricted zone on previous visit']);

        // ── Visit Requests ──
        // Approved & checked in
        $vr1 = VisitRequest::create(['visitor_id' => $v1->id, 'host_id' => $host1->id, 'site_id' => $hq->id, 'zone_id' => $officeZone->id, 'purpose' => 'Network equipment demo', 'visitor_type' => 'external', 'category' => 'vendor', 'status' => 'checked_in', 'scheduled_at' => now()->subHours(2)]);
        // Approved, not yet checked in
        $vr2 = VisitRequest::create(['visitor_id' => $v2->id, 'host_id' => $host1->id, 'site_id' => $dc->id, 'zone_id' => $serverRoom->id, 'purpose' => '5G infrastructure review', 'visitor_type' => 'external', 'category' => 'vendor', 'status' => 'approved', 'scheduled_at' => now()->addHour()]);
        // Checked out
        $vr3 = VisitRequest::create(['visitor_id' => $v3->id, 'host_id' => $host2->id, 'site_id' => $hq->id, 'zone_id' => $execZone->id, 'purpose' => 'Contract negotiation meeting', 'visitor_type' => 'external', 'category' => 'vip', 'status' => 'checked_out', 'scheduled_at' => now()->subDays(1)]);
        // Pending
        $vr4 = VisitRequest::create(['visitor_id' => $v4->id, 'host_id' => $host1->id, 'site_id' => $hq->id, 'zone_id' => $lobbyZone->id, 'purpose' => 'AC maintenance work', 'visitor_type' => 'external', 'category' => 'contractor', 'status' => 'pending', 'scheduled_at' => now()->addDay()]);
        // Pending job applicant
        $vr5 = VisitRequest::create(['visitor_id' => $v5->id, 'host_id' => $host2->id, 'site_id' => $hq->id, 'zone_id' => $lobbyZone->id, 'purpose' => 'Software Engineer interview', 'visitor_type' => 'external', 'category' => 'job_applicant', 'status' => 'pending', 'scheduled_at' => now()->addDays(2)]);
        // Rejected (blacklisted visitor)
        $vr6 = VisitRequest::create(['visitor_id' => $v6->id, 'host_id' => $host1->id, 'site_id' => $dc->id, 'zone_id' => $serverRoom->id, 'purpose' => 'Equipment installation', 'visitor_type' => 'external', 'category' => 'vendor', 'status' => 'rejected', 'scheduled_at' => now()->subDays(3)]);

        // Past visits for trends
        foreach (range(1, 5) as $day) {
            VisitRequest::create(['visitor_id' => $v1->id, 'host_id' => $host1->id, 'site_id' => $hq->id, 'zone_id' => $officeZone->id, 'purpose' => 'Follow-up meeting', 'visitor_type' => 'external', 'category' => 'general', 'status' => 'checked_out', 'scheduled_at' => now()->subDays($day)]);
            if ($day <= 3) {
                VisitRequest::create(['visitor_id' => $v2->id, 'host_id' => $host2->id, 'site_id' => $hq->id, 'zone_id' => $lobbyZone->id, 'purpose' => 'Internal transfer meeting', 'visitor_type' => 'internal', 'category' => 'general', 'status' => 'checked_out', 'scheduled_at' => now()->subDays($day)]);
            }
        }

        // ── Approvals ──
        VisitApproval::create(['visit_request_id' => $vr1->id, 'approver_id' => $admin?->id ?? $receptionist->id, 'action' => 'approved', 'acted_at' => now()->subHours(3)]);
        VisitApproval::create(['visit_request_id' => $vr2->id, 'approver_id' => $admin?->id ?? $receptionist->id, 'action' => 'approved', 'acted_at' => now()->subHour()]);
        VisitApproval::create(['visit_request_id' => $vr3->id, 'approver_id' => $cxoPa->id, 'action' => 'approved', 'acted_at' => now()->subDays(1)->subHours(2)]);
        VisitApproval::create(['visit_request_id' => $vr6->id, 'approver_id' => $security->id, 'action' => 'rejected', 'remarks' => 'Visitor is blacklisted due to previous security incident', 'acted_at' => now()->subDays(3)]);

        // ── Check-Ins ──
        $ci1 = CheckIn::create(['visit_request_id' => $vr1->id, 'visitor_id' => $v1->id, 'checked_in_by' => $receptionist->id, 'checked_in_at' => now()->subHours(2), 'badge_number' => 'VB-001']);
        $ci3 = CheckIn::create(['visit_request_id' => $vr3->id, 'visitor_id' => $v3->id, 'checked_in_by' => $receptionist->id, 'checked_in_at' => now()->subDays(1)->subHours(1), 'checked_out_at' => now()->subDays(1)->addHours(3), 'checked_out_by' => $receptionist->id, 'badge_number' => 'VB-002']);

        // ── Badges ──
        Badge::create(['check_in_id' => $ci1->id, 'visitor_id' => $v1->id, 'badge_type' => 'visitor', 'access_level' => 'standard', 'printed_at' => now()->subHours(2), 'expires_at' => now()->endOfDay()]);
        Badge::create(['check_in_id' => $ci3->id, 'visitor_id' => $v3->id, 'badge_type' => 'vip', 'access_level' => 'executive', 'printed_at' => now()->subDays(1), 'expires_at' => now()->subDays(1)->endOfDay()]);

        // ── Visitor Logs ──
        VisitorLog::create(['visitor_id' => $v1->id, 'check_in_id' => $ci1->id, 'zone_id' => $lobbyZone->id, 'access_point' => 'Main Entrance', 'action' => 'entry', 'logged_at' => now()->subHours(2)]);
        VisitorLog::create(['visitor_id' => $v1->id, 'check_in_id' => $ci1->id, 'zone_id' => $officeZone->id, 'access_point' => 'Floor 4 Access', 'action' => 'entry', 'logged_at' => now()->subHours(1)->subMinutes(45)]);

        // ── Alerts ──
        Alert::create(['type' => 'blacklist', 'severity' => 'critical', 'visit_request_id' => $vr6->id, 'visitor_id' => $v6->id, 'message' => 'Blacklisted visitor Chen Wei attempted to request access to Data Center']);
        Alert::create(['type' => 'overstay', 'severity' => 'medium', 'visitor_id' => $v1->id, 'message' => 'Visitor Abebe Worku has been on-site for over 4 hours beyond scheduled time']);
        Alert::create(['type' => 'unauthorized', 'severity' => 'high', 'message' => 'Unregistered badge scan detected at Server Room access point', 'acknowledged_by' => $security->id, 'acknowledged_at' => now()->subHours(5)]);

        echo "✅ VMS seeded: 3 sites, 6 depts, 5 zones, 6 users, 6 visitors, 13 visit requests, 4 approvals, 2 check-ins, 2 badges, 2 logs, 3 alerts\n";
    }
}
