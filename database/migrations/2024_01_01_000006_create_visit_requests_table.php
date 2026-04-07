<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purpose');
            $table->enum('visitor_type', ['external', 'internal'])->default('external');
            $table->enum('category', ['general', 'contractor', 'vendor', 'vip', 'job_applicant', 'other'])->default('general');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'checked_in', 'checked_out', 'expired'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->uuid('group_id')->nullable();
            $table->text('notes')->nullable();
            $table->string('qr_code')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_requests');
    }
};
