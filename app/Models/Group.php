<?php

namespace App\Models;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    // the length of the invite code generated for a group upon creation
    public const LENGTH_INVITE_CODE = 10;

    // member can not add multiple players to group
    public const SINGLE_PLAYER = 0;

    // member can add multiple players to group
    public const MULTIPLE_PLAYERS = 1;

    // maximum number of members in a group
    public const MEMBER_LIMIT = 30;

    // maximum number of players for a player who can have multiple
    public const PLAYER_LIMIT = 5;

    // minimum number of admins that have to be in a group
    public const MIN_NUMBER_ADMINS = 1;

    /**
    * The attributes that should be hidden for arrays.
    *
    * @var array
    */
    protected $hidden = [
        'id'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'owner_id',
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
    *
    * @return void
    */
    protected static function booted(): void
    {
        static::creating(function ($group) {
            $group->ulid = Str::ulid();
            $group->invite_code = substr(str_shuffle("23456789ABCDEFGHJKLMNPQRSTUVWXYZ"), 0, self::LENGTH_INVITE_CODE);
        });

        static::created(function ($group) {
            // add the owner of the group as a member
            $member = $group->members()->save(new Member([
                'user_id' => $group->owner_id,
                'role' => GroupRole::GROUP_ADMIN->value,
            ]));
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

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
