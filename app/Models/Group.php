<?php

namespace App\Models;

use App\DTO\ValidatedMemberData;
use App\Services\Contracts\MemberCommandInterface;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    // TODO: move these to an enum

    /**
     * Length of the invite code generated for a group on creation.
     *
     * @var int
     */
    public const LENGTH_INVITE_CODE = 10;

    /**
     * Default member limit assigned to newly created groups.
     *
     * @var int
     */
    public const INITIAL_MEMBER_LIMIT = 30;

    /**
     * Default player limit for groups that allow multiple players.
     *
     * @var int
     */
    public const INITIAL_PLAYER_LIMIT = 3;

    /**
     * Default player limit for standard self-service membership flows.
     *
     * @var int
     */
    public const REGULAR_MEMBER_PLAYER_LIMIT = 1;

    /**
     * Default follow limit assigned to newly created groups.
     *
     * @var int
     */
    public const INITIAL_FOLLOW_LIMIT = 1;

    /**
     * Minimum number of admins that must remain in a group.
     *
     * @var int
     */
    public const MIN_NUMBER_ADMINS = 1;

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
        'enabled_prediction_policies',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled_prediction_policies' => 'array',
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
                $group->invite_code = substr(str_shuffle('23456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, self::LENGTH_INVITE_CODE);
            }
            if (! $group->member_limit) {
                $group->member_limit = self::INITIAL_MEMBER_LIMIT;
            }
            if (! $group->player_limit) {
                $group->player_limit = self::INITIAL_PLAYER_LIMIT;
            }
            if (! $group->follow_limit) {
                $group->follow_limit = self::INITIAL_FOLLOW_LIMIT;
            }
            if ($group->enabled_prediction_policies === null) {
                $group->enabled_prediction_policies = [];
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

    /**
     * Render enabled prediction policy labels as badge markup.
     *
     * @return HtmlString|string A badge wrapper for enabled policies or a plain fallback when none are enabled.
     */
    public function getEnabledPredictionPoliciesDisplayAttribute(): HtmlString|string
    {
        $enabledPolicyKeys = $this->enabled_prediction_policies ?? [];

        if ($enabledPolicyKeys === []) {
            return 'None enabled';
        }

        // Resolve labels from the evaluator so the UI stays aligned with the active rule registry.
        $labelsByKey = collect(app(PredictionPolicyEvaluatorInterface::class)->groupRules())
            ->mapWithKeys(fn ($rule): array => [$rule->key() => $rule->label()]);

        $badges = collect($enabledPolicyKeys)
            ->map(fn (string $policyKey): string => (string) ($labelsByKey[$policyKey] ?? $policyKey))
            ->map(fn (string $label): string => '<span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200">'.e($label).'</span>')
            ->implode(' ');

        return new HtmlString('<div class="flex flex-wrap gap-2">'.$badges.'</div>');
    }

    /**
     * Determine whether a prediction policy key is enabled for the group.
     *
     * @param  string  $policyKey  The policy key to check.
     * @return bool True when the configured policy list contains the key.
     */
    public function isPredictionPolicyEnabled(string $policyKey): bool
    {
        return in_array($policyKey, $this->enabled_prediction_policies ?? [], true);
    }
}
