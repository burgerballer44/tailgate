<?php

use App\Models\Group;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->string('prediction_scoring_policy')
                ->default(Group::DEFAULT_PREDICTION_SCORING_POLICY)
                ->after('follow_limit');
        });

        DB::table('groups')
            ->whereNull('prediction_scoring_policy')
            ->update(['prediction_scoring_policy' => Group::DEFAULT_PREDICTION_SCORING_POLICY]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('prediction_scoring_policy');
        });
    }
};
