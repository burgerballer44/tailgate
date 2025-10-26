<?php

namespace App\Models;

use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

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
        // generate ULID on creating
        static::creating(function ($user) {
            $user->ulid = Str::ulid();
        });
    }

    /**
     * Activate the user by setting their status to ACTIVE.
     *
     * @return void
     */
    public function activate()
    {
        $this->status = UserStatus::ACTIVE;
    }

    /**
     * Scope to filter users based on the provided filters.
     */
    #[Scope]
    protected function filter(Builder $builder, array $query)
    {
        if ($q = $query['q'] ?? null) {
            $builder->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
        }

        if ($status = $query['status'] ?? null) {
            $builder->where('status', $status);
        }

        if ($role = $query['role'] ?? null) {
            $builder->where('role', $role);
        }
    }

}