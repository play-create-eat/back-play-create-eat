<?php

use App\Models\Package;
use App\Models\Timeline;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('package_timeline');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('package_timeline', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Package::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Timeline::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }
};
