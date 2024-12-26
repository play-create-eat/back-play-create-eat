<?php

use App\Enums\GenderEnum;
use App\Enums\IdTypeEnum;
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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)
            ->constrained()
            ->cascadeOnDelete()
            ->cascadeOnUpdate();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', GenderEnum::values());
            $table->string('id_number');
            $table->enum('id_type', IdTypeEnum::values());

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
