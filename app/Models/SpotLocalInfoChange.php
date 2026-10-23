<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 現地の情報の変更の履歴の1行（1回の保存で変わった1項目）（#200）
 */
class SpotLocalInfoChange extends Model
{
    // 書きかえない記録なので、updated_at は持たない
    public const UPDATED_AT = null;

    protected $fillable = [
        'spot_id',
        'user_id',
        'field',
        'old_value',
        'new_value',
        'action',
        'reverted_change_id',
    ];

    public function spot(): BelongsTo
    {
        return $this->belongsTo(Spot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revertedChange(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverted_change_id');
    }
}
