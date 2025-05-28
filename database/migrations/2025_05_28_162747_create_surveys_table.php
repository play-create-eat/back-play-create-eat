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
            
            // Play section
            $table->boolean('play_interesting')->nullable();
            $table->boolean('play_safe')->nullable();
            $table->boolean('play_staff_friendly')->nullable();
            
            // Create section
            $table->boolean('create_activities_interesting')->nullable();
            $table->boolean('create_staff_friendly')->nullable();
            
            // Eat section
            $table->string('eat_liked_food')->nullable(); // yes/no/cannot_judge
            $table->string('eat_liked_drinks')->nullable(); // yes/no/cannot_judge
            $table->string('eat_liked_pastry')->nullable(); // yes/no/cannot_judge
            $table->string('eat_team_friendly')->nullable(); // yes/no/cannot_judge
            
            // Conclusion section
            $table->text('conclusion_suggestions')->nullable();
            
            // Optional: Add user tracking if needed
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