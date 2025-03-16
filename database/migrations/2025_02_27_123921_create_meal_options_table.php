<?php

use App\Models\Meal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meal_options', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Meal::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('option_type');
            $table->string('option_name');
            $table->decimal('additional_price')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_options');
    }
};
