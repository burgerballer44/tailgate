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
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->index();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('season_id');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->onDelete('restrict');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('restrict');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};