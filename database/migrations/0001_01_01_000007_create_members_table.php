<?php

use App\Models\GroupRole;
use App\Models\MemberStatus;
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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', GroupRole::values())->default(GroupRole::GROUP_MEMBER->value);
            $table->enum('status', MemberStatus::values())->default(MemberStatus::APPROVED->value);
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['group_id', 'user_id']);
            $table->index(['group_id', 'status']);
            $table->index(['group_id', 'left_at']);
            $table->index(['group_id', 'role']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
