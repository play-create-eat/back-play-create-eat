<?php

use App\Enums\InvitationStatusEnum;
use App\Models\Family;
use App\Models\User;
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
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('code')
                ->unique();
            $table->string('email')
                ->nullable();
            $table->foreignIdFor(Family::class)
                ->nullable();
            $table->foreignIdFor(User::class, 'creator_id');
            $table->enum('status', InvitationStatusEnum::values())
                ->default(InvitationStatusEnum::PENDING->value);
            $table->timestamp('expired_at')
                ->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
