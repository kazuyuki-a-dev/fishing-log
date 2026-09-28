<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'spot_id',
        'went_at',
        'time_of_day',
        'visibility',
        'tide',
        'weather',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'went_at' => 'datetime',
        ];
    }

    public function scopeVisibleWithSpotTo(Builder $query, User $user): void
    {
        $query->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere(function ($query) {
                    $query->where('visibility', 'public')
                        ->whereHas('spot', fn($spot) => $spot->where('visibility', 'public'));
                });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function spot(): BelongsTo
    {
        return $this->belongsTo(Spot::class);
    }

    // この釣行で釣れた魚（0件なら坊主）
    public function catches(): HasMany
    {
        return $this->hasMany(FishCatch::class);
    }
}
