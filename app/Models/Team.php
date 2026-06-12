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
    const UNKNOWN_ORGANIZATION = 'Unknown';

    const UNKNOWN_CONFERENCE = 'Unknown';

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
        static::creating(function ($team) {
            $team->ulid = Str::ulid();
        });
    }

    /**
     * Get the sports associated with the team.
     */
    public function sports(): HasMany
    {
        return $this->hasMany(TeamSport::class);
    }

    /**
     * Get the team's sports as HTML entities for compact table display.
     *
     * Methods ending with "Attribute" are treated as accessors in Laravel.
     * This allows you to access $team->sports_html_entities directly.
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
     * Get a styled color badge for table display.
     *
     * Returns an HtmlString when the color is a valid hex code, the raw color
     * string when present but invalid, or null when no color is set.
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
     * Get a styled logo thumbnail badge for table display.
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
     * Get a friendly display name for the team.
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
     * Scope to filter teams based on the provided filters.
     */
    #[Scope]
    protected function filter(Builder $builder, array $query)
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
    }
}
