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
    ];

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->where(function ($query) use ($user) {
            $query->where('visibility', 'public')
                ->orWhere('created_by', $user->id);
        });
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
