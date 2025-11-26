<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
     * Scope to filter teams based on the provided filters.
     */
    public function scopeFilter($query, array $filters)
    {
        if (isset($filters['sport'])) {
            $query->whereHas('sports', function ($q) use ($filters) {
                $q->where('sport', $filters['sport']);
            });
        }

        if (isset($filters['name'])) {
            $query->where('designation', 'LIKE', "%{$filters['name']}%")
                ->orWhere('mascot', 'LIKE', "%{$filters['name']}%");
        }

        return $query;
    }
}
