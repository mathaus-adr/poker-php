<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoundPlayer extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'status' => 'boolean',
        'user_info' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roomRound(): BelongsTo
    {
        return $this->belongsTo(RoomRound::class);
    }
}
