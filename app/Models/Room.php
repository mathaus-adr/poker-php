<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function round(): HasOne
    {
        return $this->hasOne(RoomRound::class)->latest();
    }

    public function roomUsers(): HasMany
    {
        return $this->hasMany(RoomUser::class);
    }

    public function actions()
    {
        return $this->hasManyThrough(RoundAction::class, RoomRound::class, 'room_id', 'room_round_id');
    }
}
