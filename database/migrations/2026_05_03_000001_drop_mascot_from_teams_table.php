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
        if (! Schema::hasColumn('teams', 'mascot')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('mascot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('teams', 'mascot')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->string('mascot')->nullable()->after('designation');
        });
    }
};
