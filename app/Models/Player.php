<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Player extends Model
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
        'member_id',
        'player_name',
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
        static::creating(function ($player) {
            $player->ulid = Str::ulid();
        });
    }

    /**
     * Scope to filter players based on the provided filters.
     */
    #[Scope]
    protected function filter(Builder $builder, array $filters)
    {
        if (isset($filters['member_id'])) {
            $builder->where('member_id', $filters['member_id']);
        }

        if ($q = $filters['q'] ?? null) {
            $builder->whereRaw('LOWER(player_name) LIKE LOWER(?)', ["%{$q}%"]);
        }

        return $builder;
    }

    public function scores(): HasMany
    {
        return $this->hasMany(Score::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
