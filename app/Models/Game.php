<?php

namespace App\Models;

use App\Models\Enums\HtmlEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Game extends Model
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

    protected $with = ['homeTeam', 'awayTeam'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'season_id',
        'home_team_id',
        'away_team_id',
        'home_team_score',
        'away_team_score',
        'start_date_time',
        'start_time_tbd',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'home_team_score' => 'integer',
        'away_team_score' => 'integer',
        'start_time_tbd' => 'boolean',
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
     * Register model lifecycle hooks used to seed identifiers.
     */
    protected static function booted(): void
    {
        static::creating(function ($game) {
            $game->ulid = Str::ulid();
        });
    }

    /**
     * Get the home team for this game.
     *
     * @return BelongsTo The home-team relationship used for display and scoring.
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the away team for this game.
     *
     * @return BelongsTo The away-team relationship used for display and scoring.
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the season that owns this game.
     *
     * @return BelongsTo The season relationship for the game.
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Get the predictions made for this game.
     *
     * @return HasMany The game prediction collection.
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Get the start time TBD state as an HTML entity for human-friendly display.
     *
     * @return HtmlString The icon representing whether the game start time is still TBD.
     */
    public function getStartTimeTbdToFinalizedHtmlEntityAttribute(): HtmlString
    {
        return new HtmlString(
            ($this->start_time_tbd ? HtmlEntity::QUESTION_MARK : HtmlEntity::CHECK_MARK)->entity()
        );
    }
}
