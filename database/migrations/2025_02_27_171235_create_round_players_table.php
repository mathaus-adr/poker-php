<?php

use App\Models\RoomRound;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('round_players', function (Blueprint $table) {
            $table->id();
            $table->boolean('status');
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(RoomRound::class);
            $table->integer('order');
            $table->timestamps();
            $table->index(['user_id', 'room_round_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_players');
    }
};
