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
        Schema::table('product_packages', function (Blueprint $table) {
            $table->boolean('campaign_active')->default(false);
            $table->date('campaign_start_date')->nullable();
            $table->date('campaign_end_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_packages', function (Blueprint $table) {
            $table->dropColumn('campaign_active');
            $table->dropColumn('campaign_start_date');
            $table->dropColumn('campaign_end_date');
        });
    }
};
