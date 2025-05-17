<?php

use App\Models\Celebration;
use App\Models\MenuItem;
use App\Models\ModifierOption;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('celebration_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Celebration::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(MenuItem::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('child_name')->nullable();
            $table->enum('audience', ['children', 'parents'])->default('children');
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celebration_menus');
    }
};
