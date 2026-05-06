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
        if (! Schema::hasTable('seasons')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('seasons', 'season_start') ? 'season_start' : null,
            Schema::hasColumn('seasons', 'season_end') ? 'season_end' : null,
            Schema::hasColumn('seasons', 'active_date') ? 'active_date' : null,
            Schema::hasColumn('seasons', 'inactive_date') ? 'inactive_date' : null,
        ]));

        if ($columns === []) {
            return;
        }

        Schema::table('seasons', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('seasons')) {
            return;
        }

        Schema::table('seasons', function (Blueprint $table) {
            if (! Schema::hasColumn('seasons', 'season_start')) {
                $table->string('season_start')->nullable();
            }

            if (! Schema::hasColumn('seasons', 'season_end')) {
                $table->string('season_end')->nullable();
            }

            if (! Schema::hasColumn('seasons', 'active_date')) {
                $table->date('active_date')->nullable();
            }

            if (! Schema::hasColumn('seasons', 'inactive_date')) {
                $table->date('inactive_date')->nullable();
            }
        });
    }
};