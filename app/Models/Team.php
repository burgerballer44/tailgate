<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'sport',
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
     * Scope to filter teams based on the provided filters.
     */
    #[Scope]
    public function scopeFilter($query, array $filters)
    {
        if (isset($filters['sport'])) {
            $query->where('sport', $filters['sport']);
        }

        if (isset($filters['name'])) {
            $query->where('designation', 'LIKE', "%{$filters['name']}%")
                ->orWhere('mascot', 'LIKE', "%{$filters['name']}%");
        }

        return $query;
    }
}
