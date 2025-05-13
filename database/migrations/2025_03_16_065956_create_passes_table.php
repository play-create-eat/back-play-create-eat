<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->string('serial', 50)->unique();
            $table->unsignedInteger('remaining_time')->default(0);
            $table->boolean('is_extendable')->default(false);
            $table->foreignIdFor(\App\Models\Child::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignIdFor(\Bavix\Wallet\Models\Transfer::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->dateTime('entered_at')->nullable();
            $table->dateTime('exited_at')->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passes');
    }
};
