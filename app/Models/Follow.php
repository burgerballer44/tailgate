<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Follow extends Model
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
        'group_id',
        'team_id',
        'sport',
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
     */
    protected static function booted(): void
    {
        static::creating(function ($follow) {
            $follow->ulid = Str::ulid();
        });
    }

    /**
     * Get the team being followed.
     *
     * @return BelongsTo The followed team relationship.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the group that owns the follow.
     *
     * @return BelongsTo The owning group relationship.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
