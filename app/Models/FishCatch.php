<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishCatch extends Model
{
    use HasFactory;

    protected $table = 'catches';

    protected $fillable = [
        'fish_species',
        'method',
        'method_detail',
        'length_cm',
        'weight_g',
        'notes',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
