<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->boolean('is_whitelisted')->default(false)->after('is_blacklisted');
            $table->date('whitelist_expires_at')->nullable()->after('is_whitelisted');
            $table->string('whitelist_reason')->nullable()->after('whitelist_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['is_whitelisted', 'whitelist_expires_at', 'whitelist_reason']);
        });
    }
};
