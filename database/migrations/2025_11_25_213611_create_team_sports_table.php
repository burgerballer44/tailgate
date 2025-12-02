<?php

use App\Models\Sport;
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
            $table->timestamps();

            $table->unique(['team_id', 'sport']);
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
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
