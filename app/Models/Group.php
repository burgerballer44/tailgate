<?php

namespace App\Models;

use App\DTO\ValidatedMemberData;
use App\Models\Enums\GroupRole;
use App\Models\Enums\GroupThresholdRule;
use App\Models\Enums\HtmlEntity;
use App\Models\Enums\InitialGroupLimitRule;
use App\Services\Contracts\MemberCommandInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Group extends Model
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
        'owner_id',
        'member_limit',
        'player_limit',
        'follow_limit',
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
     * Register model lifecycle hooks used to seed identifiers and defaults.
     */
    protected static function booted(): void
    {
        static::creating(function ($group) {
            $group->ulid = Str::ulid();
            if (! $group->invite_code) {
                $group->invite_code = substr(str_shuffle('23456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, GroupThresholdRule::INVITE_CODE_LENGTH->value());
            }
            if (! $group->member_limit) {
                $group->member_limit = InitialGroupLimitRule::MEMBER_LIMIT->value();
            }
            if (! $group->player_limit) {
                $group->player_limit = InitialGroupLimitRule::PLAYER_LIMIT->value();
            }
            if (! $group->follow_limit) {
                $group->follow_limit = InitialGroupLimitRule::FOLLOW_LIMIT->value();
            }
        });

        static::created(function (Group $group): void {
            app(MemberCommandInterface::class)->createForGroup(
                $group,
                ValidatedMemberData::fromArray([
                    'user_id' => $group->owner_id,
                    'role' => GroupRole::GROUP_ADMIN,
                ])
            );
        });
    }

    /**
     * Filter groups by search term and exact field matches.
     *
     * Supported filters are `q`, `owner_id`, and `name`.
     *
     * @param  Builder  $builder  The query builder to constrain.
     * @param  array<string, mixed>  $filters  Associative filter input from the caller.
     * @return Builder The constrained builder instance.
     */
    #[Scope]
    public static function filter(Builder $builder, array $filters): Builder
    {
        if ($q = $filters['q'] ?? null) {
            $builder->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$q}%"])
                    ->orWhereRaw('LOWER(invite_code) LIKE LOWER(?)', ["%{$q}%"]);
            });
        }

        if (isset($filters['owner_id'])) {
            $builder->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['name'])) {
            $builder->where('name', 'like', '%'.$filters['name'].'%');
        }

        return $builder;
    }

    /**
     * Get the owning user for the group.
     *
     * @return BelongsTo The owner relationship.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the membership record for the owner when it exists.
     *
     * @return HasOne The owner's member record relationship.
     */
    public function ownerMember(): HasOne
    {
        return $this->hasOne(Member::class)->where('user_id', $this->owner_id);
    }

    /**
     * Get the direct follow record for this group.
     *
     * @return HasOne The primary follow relationship.
     */
    public function follow(): HasOne
    {
        return $this->hasOne(Follow::class);
    }

    /**
     * Get all follow records associated with the group.
     *
     * @return HasMany The follow collection relationship.
     */
    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    /**
     * Get the explicit season follows configured for the group (active only).
     *
     * @return HasMany The season-follow relationship.
     */
    public function seasonFollows(): HasMany
    {
        $relation = $this->hasMany(GroupSeasonFollow::class);

        static $hasUnfollowedAtColumn = null;

        if ($hasUnfollowedAtColumn === null) {
            $hasUnfollowedAtColumn = Schema::hasColumn('group_season_follows', 'unfollowed_at');
        }

        if ($hasUnfollowedAtColumn) {
            $relation->whereNull('unfollowed_at');
        }

        return $relation;
    }

    /**
     * Get all season follows including historical unfollowed seasons.
     *
     * @return HasMany The season-follow relationship including historical data.
     */
    public function allSeasonFollows(): HasMany
    {
        return $this->hasMany(GroupSeasonFollow::class);
    }

    /**
     * Get the admin membership records for the group.
     *
     * @return HasMany The admin membership relationship.
     */
    public function admin(): HasMany
    {
        return $this->hasMany(Member::class)->where('role', GroupRole::GROUP_ADMIN);
    }

    /**
     * Get all membership records for the group.
     *
     * @return HasMany The member relationship.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get the players that belong to the group's members.
     *
     * @return HasManyThrough The player relationship reached through members.
     */
    public function players(): HasManyThrough
    {
        return $this->hasManyThrough(Player::class, Member::class);
    }

    /**
     * Check whether the given user can administer the group.
     *
     * This reuses an already-loaded members relation when available to avoid an
     * extra lookup in common authorization paths.
     *
     * @param  User  $user  The user to evaluate.
     * @return bool True when the user owns the group or has an admin member record.
     */
    public function isAdminOrOwner(User $user): bool
    {
        if ($this->owner_id === $user->id) {
            return true;
        }

        $member = $this->relationLoaded('members')
            // Prefer the hydrated relation when possible so authorization checks do not
            // trigger an additional query during list rendering or policy evaluation.
            ? $this->members->first(fn (Member $member) => $member->user_id === $user->id)
            : $this->members()->where('user_id', $user->id)->first();

        return $member && $member->isAdmin();
    }

    /**
     * Determine whether the group currently follows at least one team.
     *
     * @return bool True when any follow record exists.
     */
    public function isFollowingTeam(): bool
    {
        return $this->follows()->exists();
    }

    /**
     * Determine whether the group explicitly follows the given season.
     *
     * @param  Season|int  $season  The season model or ID to check.
     * @return bool True when a group-season follow exists.
     */
    public function isFollowingSeason(Season|int $season): bool
    {
        $seasonId = $season instanceof Season ? $season->id : $season;

        if ($this->relationLoaded('seasonFollows')) {
            return $this->seasonFollows->contains('season_id', $seasonId);
        }

        return $this->seasonFollows()->where('season_id', $seasonId)->exists();
    }

    /**
     * Get the IDs of explicitly followed seasons.
     *
     * @return Collection<int, int>
     */
    public function getFollowedSeasonIdsAttribute(): Collection
    {
        return $this->relationLoaded('seasonFollows')
            ? $this->seasonFollows->pluck('season_id')->values()
            : $this->seasonFollows()->pluck('season_id')->values();
    }

    /**
     * Get follows as a collection, reusing a loaded relation when available.
     *
     * @return Collection<int, Follow> The loaded or freshly queried follow collection.
     */
    public function getFollowCollectionAttribute(): Collection
    {
        // Keep the caller's eager-loaded collection intact so display code does not trigger a second query.
        return $this->relationLoaded('follows')
            ? $this->follows
            : $this->follows()->with('team')->get();
    }

    /**
     * Render the group's follow state as a single HTML entity.
     *
     * @return HtmlString The icon used in compact views.
     */
    public function getFollowHtmlEntityAttribute(): HtmlString
    {
        $isFollowing = $this->relationLoaded('follows')
            ? $this->follows->isNotEmpty()
            : $this->isFollowingTeam();

        return new HtmlString(HtmlEntity::forBoolean($isFollowing)->entity());
    }

}
