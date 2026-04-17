<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FR-002: Internal visitor fields + FR-004: Meeting location
        Schema::table('visit_requests', function (Blueprint $table) {
            $table->string('meeting_location')->nullable()->after('zone_id');
            $table->integer('expected_duration_hours')->nullable()->after('scheduled_at');
            $table->string('parking_number')->nullable()->after('qr_code');
        });

        // Process flow: Car plate number for visitors
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('car_plate_number')->nullable()->after('photo');
        });

        // FR-005: QR scan check-in token
        Schema::table('check_ins', function (Blueprint $table) {
            $table->boolean('checked_in_via_qr')->default(false)->after('qr_code');
        });
    }

    public function down(): void
    {
        Schema::table('visit_requests', function (Blueprint $table) {
            $table->dropColumn(['meeting_location', 'expected_duration_hours', 'parking_number']);
        });
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn('car_plate_number');
        });
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropColumn('checked_in_via_qr');
        });
    }
};
