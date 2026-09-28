<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Spot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prefecture',
        'visibility',
        'caution_type',
        'parking_type',
        'parking_note',
        'toilet_available',
        'toilet_note',
        'convenience_distance_m',
        'facility_note',
        'notes',
        'latitude',
        'longitude'
    ];

    /**
     * この人が見てよい釣り場だけに絞る（NF-01）
     * - ログインしている人：公開か、自分が登録したもの
     * - ゲスト（$user が null）：公開のものだけ
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        $query->where(function ($query) use ($user) {
            $query->where('visibility', 'public');
            if ($user) {
                $query->orWhere('created_by', $user->id);
            }
        });
    }

    /**
     * 見る人に合わせた位置（FN-12・NF-01）
     * 本人には正確な位置、ほかの人には小数第2位で切り捨てた位置（約1km四方）
     */
    public function locationFor(?User $user): ?array
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        if ($user && $this->created_by === $user->id) {
            return ['lat' => (float) $this->latitude, 'lng' => (float) $this->longitude, 'exact' => true];
        }

        return [
            'lat' => floor(round($this->latitude * 100, 6)) / 100,
            'lng' => floor(round($this->longitude * 100, 6)) / 100,
            'exact' => false,
        ];
    }
    // 最初に登録したユーザー
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 現地情報を最後に更新したユーザー
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // この釣り場での釣行
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
