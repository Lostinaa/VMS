<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_requests', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('site_id')->constrained()->nullOnDelete();
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->string('id_photo_path')->nullable()->after('photo');
        });

        Schema::table('screening_questions', function (Blueprint $table) {
            $table->string('category')->nullable()->after('applies_to');
        });
    }

    public function down(): void
    {
        Schema::table('visit_requests', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn('id_photo_path');
        });

        Schema::table('screening_questions', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
