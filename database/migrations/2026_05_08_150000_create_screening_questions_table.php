<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screening_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question_text');
            $table->string('question_text_am')->nullable(); // Amharic translation
            $table->enum('type', ['yes_no', 'text', 'select'])->default('yes_no');
            $table->json('options')->nullable(); // For select-type questions
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->enum('applies_to', ['all', 'external', 'internal', 'vip'])->default('all');
            $table->string('flag_answer')->nullable(); // Answer that triggers a flag (e.g. "yes" for fever question)
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('screening_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('check_in_id')->constrained()->cascadeOnDelete();
            $table->foreignId('screening_question_id')->constrained()->cascadeOnDelete();
            $table->text('response');
            $table->boolean('flagged')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_responses');
        Schema::dropIfExists('screening_questions');
    }
};
