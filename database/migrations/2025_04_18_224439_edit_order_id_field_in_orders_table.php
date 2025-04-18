<?php

use App\Models\Celebration;
use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_id')) {
                return;
            }
            $table->dropForeign(['order_id']);
            $table->dropColumn('order_id');
            $table->foreignIdFor(Celebration::class)
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'order_id')) {
                return;
            }
            $table->dropForeign(['celebration_id']);
            $table->dropColumn('celebration_id');
            $table->foreignIdFor(Order::class)
                ->constrained()
                ->cascadeOnDelete();
        });
    }
};
