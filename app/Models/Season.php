<?php

namespace App\Models;

use App\Models\Score;
use App\Models\Follow;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
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
    }

    /**
     * Get the games associated with the season.
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Scope a query to filter seasons based on provided criteria.
     */
    #[Scope]
    protected function filter(Builder $builder, array $query)
    {
        if ($q = $query['q'] ?? null) {
            $builder->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$q}%"]);;
            });
        }
        
        if (isset($query['sport'])) {
            $builder->where('sport', $query['sport']);
        }

        if (isset($query['season_type'])) {
            $builder->where('season_type', $query['season_type']);
        }

        return $builder;
    }
}