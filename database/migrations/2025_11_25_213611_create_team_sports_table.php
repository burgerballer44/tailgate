<?php

use App\Models\Sport;
use App\Models\Team;
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
        Schema::create('team_sports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->enum('sport', Sport::values());
            $table->string('conference')->default(Team::UNKNOWN_CONFERENCE);
            $table->timestamps();

            $table->unique(['team_id', 'sport']);
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');

            $table->index('sport');
            $table->index('conference');
            $table->index(['sport', 'conference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_sports');
    }
};
