<?php

use App\Models\Enums\SeasonType;
use App\Models\Enums\Sport;
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
            $table->ulid('ulid')->unique();
            $table->string('name');
            $table->enum('sport', Sport::values());
            $table->enum('season_type', SeasonType::values());
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->index(['sport', 'active']);
            $table->index(['sport', 'season_type']);
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
