<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Prediction;

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
        static::creating(function ($player) {
            $player->ulid = Str::ulid();
        });
    }

    /**
     * Filter players by member and name search.
     *
     * Supported filters are `member_id` and `q`.
     *
     * @param Builder $builder The query builder to constrain.
     * @param array<string, mixed> $filters Associative filter input from the caller.
     * @return Builder The constrained builder instance.
     */
    #[Scope]
    protected function filter(Builder $builder, array $filters): Builder
    {
        if (isset($filters['member_id'])) {
            $builder->where('member_id', $filters['member_id']);
        }

        if ($q = $filters['q'] ?? null) {
            $builder->whereRaw('LOWER(player_name) LIKE LOWER(?)', ["%{$q}%"]);
        }

        return $builder;
    }

    /**
     * Get the predictions submitted for the player.
     *
     * @return HasMany The player's prediction collection.
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Get the member that owns the player profile.
     *
     * @return BelongsTo The owning member relationship.
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
