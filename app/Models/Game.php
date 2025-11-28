<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'start_date',
        'start_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'home_team_score' => 'integer',
        'away_team_score' => 'integer',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'ulid';
    }

    /**
     * Perform any actions required after the model boots.
     */
    protected static function booted(): void
    {
        static::creating(function ($game) {
            $game->ulid = Str::ulid();
        });
    }

    /**
     * Home Team
     *
     * @return [type] [description]
     */
    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Away Team
     *
     * @return [type] [description]
     */
    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Season
     *
     * @return BelongsTo
     */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
