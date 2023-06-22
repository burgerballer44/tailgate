<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Season extends Model
{
    use HasFactory;

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
        'sport',
        'season_type',
        'season_start',
        'season_end'
    ];

    /**
    * Get the route key for the model.
    *
    * @return string
    */
    public function getRouteKeyName()
    {
     return 'uuid';
    }

    /**
    * Perform any actions required after the model boots.
    *
    * @return void
    */
    protected static function booted(): void
    {
        static::creating(function ($team) {
            $team->uuid = Str::uuid()->toString();
        });
    }
}
