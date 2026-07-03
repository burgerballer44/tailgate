<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamSport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'sport',
        'conference',
    ];

    /**
     * Cast the stored sport value to the Sport enum.
     *
     * @return array<string, string> Attribute cast definitions used by Eloquent.
     */
    protected function casts(): array
    {
        return [
            'sport' => Sport::class,
        ];
    }

    /**
     * Get the team that owns the team sport.
     *
     * @return BelongsTo The owning team relationship.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
