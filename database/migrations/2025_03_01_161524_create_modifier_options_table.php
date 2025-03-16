<?php

use App\Models\ModifierGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('modifier_options', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ModifierGroup::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10)
                ->default(0);
            $table->json('nutrition_info')
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modifier_options');
    }
};
