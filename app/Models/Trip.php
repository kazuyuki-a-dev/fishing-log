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

    /**
     * この釣行が、選んだ条件（潮・時間帯）と同じか（FN-11・FN-16）
     * 時間帯が null（指定なし）なら、潮だけで判断する
     */
    public function matchesCondition(string $tide, ?string $timeOfDay): bool
    {
        return $this->tide === $tide
            && ($timeOfDay === null || $this->time_of_day === $timeOfDay);
    }

    /**
     * 実際に使う公開範囲（FN-12）
     * 釣り場が非公開なら、釣行が全体公開でも「釣り場だけ隠す」として扱う（閉じているほうを優先）
     */
    public function effectiveVisibility(): string
    {
        if ($this->visibility === 'public' && $this->spot->visibility === 'private') {
            return 'spot_hidden';
        }

        return $this->visibility;
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
