<?php

namespace App\Models;

use App\Models\Score;
use App\Models\Follow;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Season extends Model
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
        'active_date' => 'date',
        'inactive_date' => 'date',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'sport',
        'season_type',
        'season_start',
        'season_end',
        'active',
        'active_date',
        'inactive_date',
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
        static::creating(function ($season) {
            $season->ulid = Str::ulid();
        });

        static::deleting(function ($season) {
            // delete all follows for this season
            Follow::where('season_id', $season->id)->delete();
            // delete all scores for games in this season
            Score::whereHas('game', function ($query) use ($season) {
                $query->where('season_id', $season->id);
            })->delete();
            // delete all games
            $season->games()->delete();
        });
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function scopeFilter($query, array $filters)
    {
        if (isset($filters['sport'])) {
            $query->where('sport', $filters['sport']);
        }

        if (isset($filters['season_type'])) {
            $query->where('season_type', $filters['season_type']);
        }

        if (isset($filters['name'])) {
            $query->where('name', 'LIKE', "%{$filters['name']}%");
        }

        return $query;
    }
}
