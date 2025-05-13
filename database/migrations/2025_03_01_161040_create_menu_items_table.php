<?php

use App\Models\MenuCategory;
use App\Models\MenuType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(MenuCategory::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(MenuType::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
