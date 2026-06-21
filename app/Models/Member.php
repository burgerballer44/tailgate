<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Member extends Model
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
        'user_id',
        'role',
        'status',
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
        static::creating(function ($member) {
            $member->ulid = Str::ulid();
        });
    }

    /**
     * Filter members by user, group, and status.
     *
     * Supported filters are `user_id`, `group_id`, and `status`.
     *
     * @param Builder $builder The query builder to constrain.
     * @param array<string, mixed> $filters Associative filter input from the caller.
     * @return Builder The constrained builder instance.
     */
    #[Scope]
    public static function filter(Builder $builder, array $filters): Builder
    {
        if (isset($filters['user_id'])) {
            $builder->where('user_id', $filters['user_id']);
        }

        if (isset($filters['group_id'])) {
            $builder->where('group_id', $filters['group_id']);
        }

        if (isset($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        return $builder;
    }

    /**
     * Get the players that belong to the member.
     *
     * @return HasMany The player collection relationship.
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get the user that owns the member record.
     *
     * @return BelongsTo The owning user relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the group that owns the member record.
     *
     * @return BelongsTo The owning group relationship.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Check whether the member is still awaiting approval.
     *
     * @return bool True when the member status is pending.
     */
    public function isPending(): bool
    {
        return $this->status === MemberStatus::PENDING->value;
    }

    /**
     * Check whether the member has been approved.
     *
     * @return bool True when the member status is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === MemberStatus::APPROVED->value;
    }

    /**
     * Check whether this membership belongs to the group owner.
     *
     * @return bool True when the member user is the group's owner.
     */
    public function isOwner(): bool
    {
        return $this->user_id === $this->group->owner_id;
    }

    /**
     * Check whether the member has the group admin role.
     *
     * @return bool True when the role is the admin role.
     */
    public function isAdmin(): bool
    {
        return $this->role === GroupRole::GROUP_ADMIN->value;
    }

    /**
     * Check whether the given user is allowed to remove this member.
     *
     * Removal is only allowed for approved non-owner members when the acting
     * user can administer the group.
     *
     * @param User $user The user attempting the removal.
     * @return bool True when the removal is permitted.
     */
    public function canBeRemovedBy(User $user): bool
    {
        return $this->isApproved()
            // Owners are protected even when the acting user has admin access.
            && ! $this->isOwner()
            && $this->group->isAdminOrOwner($user);
    }
}
