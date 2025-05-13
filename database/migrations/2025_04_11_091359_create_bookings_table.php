<?php

use App\Models\Package;
use App\Models\User;
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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Package::class)
                ->constrained();
            $table->string('child_name');
            $table->integer('children_count');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->dateTime('setup_start_time');
            $table->dateTime('cleanup_end_time');
            $table->text('special_requests')
                ->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
