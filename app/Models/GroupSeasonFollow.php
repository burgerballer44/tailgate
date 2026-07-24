<?php

namespace App\Models;

use App\ScoringPolicies\PredictionDifferenceFromScorePointsPolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GroupSeasonFollow extends Model
{
    use HasFactory;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'group_id',
        'season_id',
        'prediction_scoring_policy',
        'enabled_prediction_policies',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled_prediction_policies' => 'array',
    ];

    /**
     * Use the ULID instead of the numeric ID for route model binding.
     *
     * @return string The route key column name.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /**
     * Register model lifecycle hooks used to seed identifiers and defaults.
     */
    protected static function booted(): void
    {
        static::creating(function (self $groupSeasonFollow): void {
            $groupSeasonFollow->ulid = Str::ulid();

            if (! $groupSeasonFollow->prediction_scoring_policy) {
                $groupSeasonFollow->prediction_scoring_policy = PredictionDifferenceFromScorePointsPolicy::key();
            }

            if ($groupSeasonFollow->enabled_prediction_policies === null) {
                $groupSeasonFollow->enabled_prediction_policies = [];
            }
        });
    }

    /**
     * Get the owning group.
     *
     * @return BelongsTo The group relationship.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Get the followed season.
     *
     * @return BelongsTo The season relationship.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
