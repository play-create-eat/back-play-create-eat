<?php

use App\Models\CartItem;
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
        Schema::create('cart_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CartItem::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ModifierOption::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_item_modifiers');
    }
};
