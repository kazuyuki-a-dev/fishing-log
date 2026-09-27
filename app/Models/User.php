<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'home_prefecture', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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

    // 自分が登録した釣り場
    public function createdSpots(): HasMany
    {
        return $this->hasMany(Spot::class, 'created_by');
    }

    // 自分が現地情報を最後に更新した釣り場
    public function editedSpots(): HasMany
    {
        return $this->hasMany(Spot::class, 'updated_by');
    }

    // 自分の釣行
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
