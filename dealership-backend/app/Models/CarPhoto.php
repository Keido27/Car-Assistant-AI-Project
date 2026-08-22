<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarPhoto extends Model
{
    protected $fillable = ['car_id', 'path', 'sort_order', 'is_cover'];

    protected $casts = [
        'is_cover' => 'boolean',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    // Returns a usable URL regardless of which S3-compatible driver is configured.
    public function getUrlAttribute(): string
    {
        return \Storage::disk(config('filesystems.default'))->url($this->path);
    }
}
