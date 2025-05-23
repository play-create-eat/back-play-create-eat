<?php

use App\Models\Cake;
use App\Models\Child;
use App\Models\Family;
use App\Models\Menu;
use App\Models\Package;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('celebrations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Family::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Child::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Package::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(Theme::class)
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('children_count')
            ->nullable();
            $table->integer('parents_count')
            ->nullable();
            $table->dateTime('celebration_date')
            ->nullable();
            $table->foreignIdFor(Cake::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->decimal('cake_weight', 5)
                ->nullable();
            $table->boolean('photographer')
                ->default(false);
            $table->boolean('photo_album')
                ->default(false);
            $table->smallInteger('current_step');
            $table->boolean('completed')
                ->default(false);
            $table->decimal('total_amount', 12)
                ->nullable();
            $table->decimal('min_amount', 12)
                ->nullable();
            $table->decimal('paid_amount', 12)
                ->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celebrations');
    }
};
