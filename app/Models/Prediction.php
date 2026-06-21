<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Prediction extends Model
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
        'player_id',
        'game_id',
        'home_team_prediction',
        'away_team_prediction',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'home_team_prediction' => 'integer',
        'away_team_prediction' => 'integer',
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
        static::creating(function ($prediction) {
            $prediction->ulid = Str::ulid();
        });
    }

    /**
     * Get the game being predicted.
     *
     * @return BelongsTo The predicted game relationship.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Get the player that submitted the prediction.
     *
     * @return BelongsTo The predicting player relationship.
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}