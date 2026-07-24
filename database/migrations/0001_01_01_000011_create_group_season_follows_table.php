<?php

use App\ScoringPolicies\PredictionDifferenceFromScorePointsPolicy;
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
        Schema::create('group_season_follows', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('season_id');
            $table->string('prediction_scoring_policy')->default(PredictionDifferenceFromScorePointsPolicy::key());
            $table->json('enabled_prediction_policies')->nullable();
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->foreign('season_id')->references('id')->on('seasons')->onDelete('cascade');

            $table->unique(['group_id', 'season_id']);
            $table->index(['group_id', 'season_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_season_follows');
    }
};
