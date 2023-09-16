<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    public const LENGTH_INVITE_CODE = 10;

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
    }

    public function scopeFilter($query, array $filters)
    {
        if (isset($filters['owner_id'])) {
            $query->where('owner_id', $filters['owner_id']);
        }

        return $query;
    }

    public function owner(): HasMany
    {
        return $this->belongsTo(User::class);
    }
}
