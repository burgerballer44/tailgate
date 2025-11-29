<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
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
        'designation',
        'mascot',
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
     * Get a string representation of the team's sports.
     * 
     * Methods ending with "Attribute" are treated as accessors in Laravel.
     */
    public function getSportsStringAttribute(): string
    {
        return $this->sports->pluck('sport')->map(fn($sport) => ucfirst($sport->value))->join(', ');
    }

    /**
     * Get the full name of the team.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->designation} {$this->mascot}";
    }

    /**
     * Scope to filter teams based on the provided filters.
     */
    #[Scope]
    protected function filter(Builder $builder, array $query)
    {
        if ($q = $query['q'] ?? null) {
            $builder->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(designation) LIKE LOWER(?)', ["%{$q}%"])
                    ->orWhereRaw('LOWER(mascot) LIKE LOWER(?)', ["%{$q}%"]);
            });
        }

        if (isset($query['sport'])) {
            $builder->whereHas('sports', function ($s) use ($query) {
                $s->where('sport', $query['sport']);
            });
        }
    }
}
