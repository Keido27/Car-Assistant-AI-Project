<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'brand',
        'model',
        'variant',
        'year',
        'price',
        'mileage',
        'transmission',
        'fuel_type',
        'condition_notes',
        'color',
        'plate_region',
        'status',
        'stock_number',
    ];

    protected $casts = [
        'year' => 'integer',
        'price' => 'integer',
        'mileage' => 'integer',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(CarPhoto::class)->orderBy('sort_order');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Scope used by both the admin dashboard filter and the bot's
     * search_inventory tool, so the two stay consistent.
     */
    public function scopeMatching($query, array $criteria)
    {
        return $query
            ->when($criteria['brand'] ?? null, fn ($q, $v) => $q->where('brand', 'like', "%{$v}%"))
            ->when($criteria['model'] ?? null, fn ($q, $v) => $q->where('model', 'like', "%{$v}%"))
            ->when($criteria['year_min'] ?? null, fn ($q, $v) => $q->where('year', '>=', $v))
            ->when($criteria['year_max'] ?? null, fn ($q, $v) => $q->where('year', '<=', $v))
            ->when($criteria['price_max'] ?? null, fn ($q, $v) => $q->where('price', '<=', $v))
            ->when($criteria['transmission'] ?? null, fn ($q, $v) => $q->where('transmission', $v))
            ->when($criteria['status'] ?? 'ready', fn ($q, $v) => $q->where('status', $v));
    }
}
