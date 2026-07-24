<?php

namespace App\Models;

use App\Models\Enums\HtmlEntity;
use App\Models\Enums\Sport;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'active' => 'boolean',
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
        'active',
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
        static::creating(function ($season) {
            $season->ulid = Str::ulid();
        });
    }

    /**
     * Get the games associated with the season.
     *
     * @return HasMany The season game relationship.
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Get the explicit group-season follows configured for this season.
     *
     * @return HasMany The season-follow relationship.
     */
    public function groupSeasonFollows(): HasMany
    {
        return $this->hasMany(GroupSeasonFollow::class);
    }

    /**
     * Render the season sport as an icon when the stored value maps cleanly.
     *
     * @return HtmlString|string An icon when the sport is known, otherwise the raw stored value.
     */
    public function getSportHtmlEntityAttribute(): HtmlString|string
    {
        $sport = Sport::tryFrom((string) $this->sport);

        if (! $sport) {
            return (string) $this->sport;
        }

        return new HtmlString($sport->htmlEntity()->entity());
    }

    /**
     * Render the active flag as a compact HTML entity.
     *
     * @return HtmlString The active-state icon used in tables and badges.
     */
    public function getActiveHtmlEntityAttribute(): HtmlString
    {
        return new HtmlString(HtmlEntity::forBoolean((bool) $this->active)->entity());
    }

    /**
     * Filter seasons by search term, sport, and season type.
     *
     * Supported filters are `q`, `sport`, and `season_type`.
     *
     * @param  Builder  $builder  The query builder to constrain.
     * @param  array<string, mixed>  $query  Associative filter input from the caller.
     * @return Builder The constrained builder instance.
     */
    #[Scope]
    protected function filter(Builder $builder, array $query): Builder
    {
        if ($q = $query['q'] ?? null) {
            $builder->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$q}%"]);
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
