<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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
        'image_path'
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * 写真の URL（写真がなければ null）
     */
    public function photoUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
