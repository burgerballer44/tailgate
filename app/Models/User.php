<?php

namespace App\Models;

use App\Models\Enums\HtmlEntity;
use App\Models\Enums\MemberStatus;
use App\Models\Enums\UserStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'last_login_at',
        'status',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string> Attribute cast definitions used by Eloquent.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
     */
    protected static function booted(): void
    {
        static::creating(function ($user) {
            $user->ulid = Str::ulid();
        });
    }

    /**
     * Get the membership records for the user.
     *
     * @return HasMany The user's membership collection.
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Get the social accounts linked to the user.
     *
     * @return HasMany The linked social account collection.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Check whether this user can sign in with a local password.
     *
     * @return bool True when a hashed password exists.
     */
    public function hasPassword(): bool
    {
        return ! empty($this->password);
    }

    /**
     * Check whether the user owns the given group.
     *
     * @param  Group  $group  The group being checked.
     * @return bool True when the user is the group owner.
     */
    public function isOwnerOf(Group $group): bool
    {
        return $group->owner_id === $this->id;
    }

    /**
     * Get the user's membership status for the given group.
     *
     * The method expects the group's members relation to be loaded, so callers
     * can avoid repeated lookups when they already have the collection in memory.
     *
     * @param  Group  $group  The group to inspect.
     * @return string|null The membership status value, or null when no membership exists.
     */
    public function getMembershipStatus(Group $group): ?string
    {
        $membership = $group->members->first(fn ($member) => $member->user_id === $this->id);

        return $membership?->status;
    }

    /**
     * Check whether the user can access the given group.
     *
     * @param  Group  $group  The group being checked.
     * @return bool True when the user owns the group or has an approved membership.
     */
    public function canAccessGroup(Group $group): bool
    {
        return $this->isOwnerOf($group) || $this->getMembershipStatus($group) === MemberStatus::APPROVED->value;
    }

    /**
     * Render the email verification state as an HTML entity.
     *
     * @return HtmlString The verification icon used in compact displays.
     */
    public function getVerifiedHtmlEntityAttribute(): HtmlString
    {
        return new HtmlString(HtmlEntity::forBoolean($this->hasVerifiedEmail())->entity());
    }

    /**
     * Mark the user as active without persisting the change immediately.
     *
     * @return void The status is updated on the model instance and can be saved by the caller.
     */
    public function activate(): void
    {
        $this->status = UserStatus::ACTIVE;
    }

    /**
     * Filter users by search term, status, and role.
     *
     * Supported filters are `q`, `status`, and `role`.
     *
     * @param  Builder  $builder  The query builder to constrain.
     * @param  array<string, mixed>  $query  Associative filter input from the caller.
     * @return Builder The constrained builder instance.
     */
    #[Scope]
    protected function filter(Builder $builder, array $query): Builder
    {
        if ($q = $query['q'] ?? null) {
            $builder->where(function ($query) use ($q) {
                $query->whereRaw('LOWER(name) LIKE LOWER(?)', ["%{$q}%"])
                    ->orWhereRaw('LOWER(email) LIKE LOWER(?)', ["%{$q}%"]);
            });
        }

        if ($status = $query['status'] ?? null) {
            $builder->where('status', $status);
        }

        if ($role = $query['role'] ?? null) {
            $builder->where('role', $role);
        }

        return $builder;
    }
}
