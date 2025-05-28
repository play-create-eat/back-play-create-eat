<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->boolean('play_interesting')->nullable();
            $table->boolean('play_safe')->nullable();
            $table->boolean('play_staff_friendly')->nullable();
            $table->boolean('create_activities_interesting')->nullable();
            $table->boolean('create_staff_friendly')->nullable();
            $table->string('eat_liked_food')->nullable();
            $table->string('eat_liked_drinks')->nullable();
            $table->string('eat_liked_pastry')->nullable();
            $table->string('eat_team_friendly')->nullable();

            $table->text('conclusion_suggestions')->nullable();
            
            $table->string('user_email')->nullable();
            $table->string('ip_address')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surveys');
    }
};
