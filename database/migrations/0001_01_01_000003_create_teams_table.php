<?php

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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->index();
            $table->string('organization');
            $table->string('designation');
            $table->string('type');
            $table->string('conference')->default(Team::UNKNOWN_CONFERENCE);
            $table->string('abbreviation')->nullable();
            $table->string('color')->nullable();
            $table->json('logos')->nullable();
            $table->json('social_media')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
