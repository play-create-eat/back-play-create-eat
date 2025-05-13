<?php

use App\Models\MenuItem;
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
        Schema::create('menu_item_modifier_group', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MenuItem::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(ModifierGroup::class)
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
        Schema::dropIfExists('menu_item_modifier_group');
    }
};
