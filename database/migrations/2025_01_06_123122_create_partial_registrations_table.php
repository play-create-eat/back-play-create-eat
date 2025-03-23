<?php

use App\Enums\IdTypeEnum;
use App\Enums\PartialRegistrationStatusEnum;
use App\Models\Family;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('partial_registrations', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('id_type', IdTypeEnum::values())->nullable();
            $table->string('id_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('password')->nullable();
            $table->foreignIdFor(Family::class)->nullable();
            $table->enum('status', PartialRegistrationStatusEnum::values())
                ->default(PartialRegistrationStatusEnum::Pending->value);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partial_registrations');
    }
};
