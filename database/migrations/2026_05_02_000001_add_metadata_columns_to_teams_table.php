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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('conference')->default('Unknown')->after('mascot');
            $table->string('abbreviation')->nullable()->after('conference');
            $table->string('color')->nullable()->after('abbreviation');
            $table->string('alternate_color')->nullable()->after('color');
            $table->json('logos')->nullable()->after('alternate_color');
            $table->json('social_media')->nullable()->after('logos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'conference',
                'abbreviation',
                'color',
                'alternate_color',
                'logos',
                'social_media',
            ]);
        });
    }
};