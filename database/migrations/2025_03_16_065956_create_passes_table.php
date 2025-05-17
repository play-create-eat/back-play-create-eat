<?php

use App\Models\Child;
use App\Models\User;
use Bavix\Wallet\Models\Transfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->string('serial', 50)->unique();
            $table->unsignedInteger('remaining_time')->default(0);
            $table->boolean('is_extendable')->default(false);
            $table->foreignIdFor(Child::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignIdFor(Transfer::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->dateTime('entered_at')->nullable();
            $table->dateTime('exited_at')->nullable();
            $table->dateTime('expires_at');
            $table->date('activation_date')
                ->default(DB::raw('CURRENT_DATE'));
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('passes');
    }
};
