<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    /**
     * Placeholder organization name used when source data is incomplete.
     *
     * @var string
     */
    public const UNKNOWN_ORGANIZATION = 'Unknown';

    /**
     * Placeholder conference name used when source data is incomplete.
     *
     * @var string
     */
    public const UNKNOWN_CONFERENCE = 'Unknown';

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
        'organization',
        'designation',
        'conference',
        'abbreviation',
        'color',
        'logos',
        'social_media',
        'type',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'logos' => 'array',
        'social_media' => 'array',
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
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function ($team) {
            $team->ulid = Str::ulid();
        });
    }

    /**
     * Get the sports associated with the team.
     *
     * @return HasMany The sports relationship.
     */
    public function sports(): HasMany
    {
        return $this->hasMany(TeamSport::class);
    }

    /**
     * Render the team's sports as compact HTML entities.
     *
     * @return HtmlString The sports icon markup used in table views.
     */
    public function getSportsHtmlEntitiesAttribute(): HtmlString
    {
        $entities = $this->sports
            ->pluck('sport')
            ->map(fn (Sport $sport) => $sport->htmlEntity()->entity())
            ->implode(' ');

        return new HtmlString($entities);
    }

    /**
     * Render the team's color as a badge when the value is a valid hex code.
     *
     * @return HtmlString|string|null A badge for valid colors, the raw string for invalid values, or null when unset.
     */
    public function getColorBadgeAttribute(): HtmlString|string|null
    {
        if (! $this->color) {
            return null;
        }

        $color = html_entity_decode($this->color, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $color)) {
            return $color;
        }

        return new HtmlString(
            '<span class="inline-flex items-center gap-x-2 rounded-full bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200">'
            .'<span class="h-3 w-3 rounded-full ring-1 ring-inset ring-black/10" style="background-color: '.e($color).';"></span>'
            .'<span>'.e($color).'</span>'
            .'</span>'
        );
    }

    /**
     * Render the first team logo as a thumbnail badge when a valid URL exists.
     *
     * @return HtmlString|null The logo badge or null when no valid logo URL is available.
     */
    public function getLogoBadgeAttribute(): ?HtmlString
    {
        if (! is_array($this->logos) || $this->logos === []) {
            return null;
        }

        $logoUrl = html_entity_decode((string) $this->logos[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            return null;
        }

        return new HtmlString(
            '<span class="inline-flex h-8 w-8 items-center justify-center overflow-hidden rounded-md bg-white ring-1 ring-inset ring-slate-200">'
            .'<img src="'.e($logoUrl).'" alt="'.e($this->designation.' logo').'" class="h-full w-full object-contain" loading="lazy" />'
            .'</span>'
        );
    }

    /**
     * Build a friendly display name for the team.
     *
     * @return string The display name, or "Unknown Team" when source fields are empty.
     */
    public function getDisplayNameAttribute(): string
    {
        $organization = trim((string) $this->organization);
        $designation = trim((string) $this->designation);
        $abbreviation = trim((string) $this->abbreviation);

        $nameParts = array_filter([$organization, $designation]);
        $name = $nameParts !== [] ? implode(' ', $nameParts) : 'Unknown Team';

        $metaParts = array_filter([$abbreviation]);

        if ($metaParts === []) {
            return $name;
        }

        return sprintf('%s (%s)', $name, implode(' | ', $metaParts));
    }

    /**
     * Filter teams by search term, sport, and type.
     *
     * Supported filters are `q`, `sport`, and `type`.
     *
     * @param Builder $builder The query builder to constrain.
     * @param array<string, mixed> $query Associative filter input from the caller.
     * @return Builder The constrained builder instance.
     */
    #[Scope]
    protected function filter(Builder $builder, array $query): Builder
    {
        if ($q = $query['q'] ?? null) {
            $builder->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(organization) LIKE LOWER(?)', ["%{$q}%"])
                    ->orWhereRaw('LOWER(designation) LIKE LOWER(?)', ["%{$q}%"])
                    ->orWhereRaw('LOWER(conference) LIKE LOWER(?)', ["%{$q}%"])
                    ->orWhereRaw('LOWER(abbreviation) LIKE LOWER(?)', ["%{$q}%"]);
            });
        }

        if (isset($query['sport'])) {
            $builder->whereHas('sports', function ($s) use ($query) {
                $s->where('sport', $query['sport']);
            });
        }

        if (isset($query['type'])) {
            $builder->where('type', $query['type']);
        }

        return $builder;
    }
}
