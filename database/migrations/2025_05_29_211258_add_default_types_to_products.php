<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('products')->update(['type' => \App\Enums\ProductTypeEnum::BASIC->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
