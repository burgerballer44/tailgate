<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
            // delete all games
            $season->games->each(function ($game) {
                $game->delete();
            });
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
