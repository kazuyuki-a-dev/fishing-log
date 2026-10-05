<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 不適切な投稿の報告（NF-04・PG21）
 * 対象は釣行（trip_id）か釣り場（spot_id）のどちらか一方
 */
class Report extends Model
{
    // reporter_id・trip_id・spot_id・status は、コントローラで決めて入れる（送られた値をそのまま入れない）
    protected $fillable = [
        'reason',
        'detail',
    ];

    // 報告した人
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    // 報告された釣行（釣り場への報告なら null）
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    // 報告された釣り場（釣行への報告なら null）
    public function spot(): BelongsTo
    {
        return $this->belongsTo(Spot::class);
    }
}
