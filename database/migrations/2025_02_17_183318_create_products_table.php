<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_time')->unsigned()->default(0);
            $table->integer('price')->unsigned();
            $table->integer('price_weekend')->unsigned()->nullable();
            $table->decimal('fee_percent', 5)->default(0);
            $table->decimal('cashback_percent', 5)->default(0);
            $table->boolean('is_extendable')->default(false);
            $table->boolean('is_available')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
