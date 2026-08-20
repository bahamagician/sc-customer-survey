<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('survey_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('source')->index();
            $table->foreignId('survey_location_id')->nullable()->index()->constrained();
            $table->foreignId('survey_subscriber_id')->nullable()->index()->constrained();
            $table->unsignedTinyInteger('overall_score')->nullable()->index();
            $table->unsignedTinyInteger('found_what_looking_for')->nullable();
            $table->unsignedTinyInteger('staff_knowledgeable')->nullable();
            $table->unsignedTinyInteger('staff_helpful')->nullable();
            $table->unsignedTinyInteger('store_clean')->nullable();
            $table->unsignedTinyInteger('prices_fair')->nullable();
            $table->json('answers');
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('follow_up_status')->nullable();
            $table->string('last_question_key')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('survey_responses');
    }
};
