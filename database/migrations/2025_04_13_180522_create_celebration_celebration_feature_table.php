<?php

use App\Models\Celebration;
use App\Models\CelebrationFeature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('celebration_celebration_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Celebration::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(CelebrationFeature::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celebration_celebration_feature');
    }
};
