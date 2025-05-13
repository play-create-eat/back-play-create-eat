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
        Schema::create('party_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_invitation_template_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('celebration_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_invitations');
    }
};
