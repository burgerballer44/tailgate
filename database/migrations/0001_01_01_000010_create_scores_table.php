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
        Schema::create('scores', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->index();
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('game_id');
            $table->string('home_team_prediction');
            $table->string('away_team_prediction');
            $table->timestamps();

            $table->foreign('player_id')->references('id')->on('players')->onDelete('restrict');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scores');
    }
};