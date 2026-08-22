<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'wa_thread_id',
        'car_id',
        'interest_summary',
        'status',
        'assigned_to',
        'last_message_at',
        'source'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class)->orderBy('sent_at');
    }

    public function scopeNeedsHandoff($query)
    {
        return $query->where('status', 'needs_handoff');
    }
}
