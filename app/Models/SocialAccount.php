<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Social login identity linked to a user account.
 */
class SocialAccount extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'avatar_url',
        'raw_profile',
        'last_login_at',
    ];

    /**
     * Cast the stored profile payload and login timestamp into native types.
     *
     * @return array<string, string> Attribute cast definitions used by Eloquent.
     */
    protected function casts(): array
    {
        return [
            'raw_profile' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the social account.
     *
     * @return BelongsTo The owning user relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
