<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishCatch extends Model
{
    use HasFactory;

    protected $table = 'catches';

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
