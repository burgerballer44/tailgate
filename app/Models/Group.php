<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    // the length of the invite code generated for a group upon creation
    public const LENGTH_INVITE_CODE = 10;

    // initial maximum number of members in a group
    public const INITIAL_MEMBER_LIMIT = 30;

    // initial maximum number of players for a player who can have multiple
    public const INITIAL_PLAYER_LIMIT = 5;

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
            $group->invite_code = substr(str_shuffle('23456789ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, self::LENGTH_INVITE_CODE);
            $group->member_limit = self::INITIAL_MEMBER_LIMIT;
            $group->player_limit = self::INITIAL_PLAYER_LIMIT;
        });

        static::created(function ($group) {
            // add the owner of the group as a member
            $member = $group->members()->save(new Member([
                'user_id' => $group->owner_id,
                'role' => GroupRole::GROUP_ADMIN->value,
            ]));
        });

        static::deleting(function ($group) {
            // delete all members
            $group->members->each(function ($member) {
                $member->delete();
            });
            // delete follow
            $group->follow?->delete();
        });
    }

    public function scopeFilter($query, array $filters)
    {
        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        if (isset($filters['invite_code'])) {
            $query->where('invite_code', $filters['invite_code']);
        }

        return $query;
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
}
