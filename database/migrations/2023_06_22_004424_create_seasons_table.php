<?php

use App\Models\SeasonType;
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
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->index();
            $table->string('name');
            $table->enum('sport', array_column(Sport::cases(), 'value'));
            $table->enum('season_type', array_column(SeasonType::cases(), 'value'));
            $table->string('season_start');
            $table->string('season_end');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
