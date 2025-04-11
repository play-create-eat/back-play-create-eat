<?php

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
        Schema::table('celebrations', function (Blueprint $table) {
            $table->decimal('total_amount', 12)
                ->nullable()
                ->change();
            $table->decimal('min_amount', 12)
                ->nullable()
                ->change();
            $table->decimal('paid_amount', 12)
                ->nullable()
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('celebrations', function (Blueprint $table) {
            $table->decimal('total_amount', 5)
                ->nullable()
                ->change();
            $table->decimal('min_amount', 5)
                ->nullable()
                ->change();
            $table->decimal('paid_amount', 5)
                ->nullable()
                ->change();
        });
    }
};
