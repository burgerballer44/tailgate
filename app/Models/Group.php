<?php

namespace App\Models;

use App\DTO\ValidatedMemberData;
use App\Services\Contracts\PredictionPolicyEvaluatorInterface;
use App\Services\Contracts\MemberCommandInterface;
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

    // the length of the invite code generated for a group upon creation
    public const LENGTH_INVITE_CODE = 10;

    // initial maximum number of members in a group
    public const INITIAL_MEMBER_LIMIT = 30;

    // initial maximum number of players for a player who can have multiple
    public const INITIAL_PLAYER_LIMIT = 3;

    // default player limit for regular self-service member management
    public const REGULAR_MEMBER_PLAYER_LIMIT = 1;

    // initial maximum number of teams a group can follow
    public const INITIAL_FOLLOW_LIMIT = 1;

    // minimum number of admins that have to be in a group
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
     * Scope to filter groups based on the provided filters.
     */
    #[Scope]
    public static function filter(Builder $builder, array $filters)
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

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ownerMember(): HasOne
    {
        return $this->hasOne(Member::class)->where('user_id', $this->owner_id);
    }

    public function follow(): HasOne
    {
        return $this->hasOne(Follow::class);
    }

    public function follows(): HasMany
    {
        return $this->hasMany(Follow::class);
    }

    public function admin(): HasMany
    {
        return $this->hasMany(Member::class)->where('role', GroupRole::GROUP_ADMIN);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function players(): HasManyThrough
    {
        return $this->hasManyThrough(Player::class, Member::class);
    }

    /**
     * Check if the given user is an admin or the owner of the group.
     */
    public function isAdminOrOwner(User $user): bool
    {
        if ($this->owner_id === $user->id) {
            return true;
        }

        $member = $this->relationLoaded('members')
            ? $this->members->first(fn (Member $member) => $member->user_id === $user->id)
            : $this->members()->where('user_id', $user->id)->first();

        return $member && $member->isAdmin();
    }

    /**
     * Check if the group is following a team.
     */
    public function isFollowingTeam(): bool
    {
        return $this->follows()->exists();
    }

    /**
     * Get follows as a loaded collection when available.
     */
    public function getFollowCollectionAttribute(): Collection
    {
        return $this->relationLoaded('follows')
            ? $this->follows
            : $this->follows()->with('team')->get();
    }

    /**
     * Get the group's follow state as an HTML entity.
     */
    public function getFollowHtmlEntityAttribute(): HtmlString
    {
        $isFollowing = $this->relationLoaded('follows')
            ? $this->follows->isNotEmpty()
            : $this->isFollowingTeam();

        return new HtmlString(HtmlEntity::forBoolean($isFollowing)->entity());
    }

    /**
     * Get enabled prediction policy labels as display-ready badge markup.
     */
    public function getEnabledPredictionPoliciesDisplayAttribute(): HtmlString|string
    {
        $enabledPolicyKeys = $this->enabled_prediction_policies ?? [];

        if ($enabledPolicyKeys === []) {
            return 'None enabled';
        }

        $labelsByKey = collect(app(PredictionPolicyEvaluatorInterface::class)->groupRules())
            ->mapWithKeys(fn ($rule): array => [$rule->key() => $rule->label()]);

        $badges = collect($enabledPolicyKeys)
            ->map(fn (string $policyKey): string => (string) ($labelsByKey[$policyKey] ?? $policyKey))
            ->map(fn (string $label): string => '<span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200">'.e($label).'</span>')
            ->implode(' ');

        return new HtmlString('<div class="flex flex-wrap gap-2">'.$badges.'</div>');
    }

    /**
     * Determine whether the given prediction policy is enabled for this group.
     */
    public function isPredictionPolicyEnabled(string $policyKey): bool
    {
        return in_array($policyKey, $this->enabled_prediction_policies ?? [], true);
    }
}
