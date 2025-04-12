<?php

use App\Models\User;
use App\Models\RoomRound;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('round_actions', function (Blueprint $table) {
            $table->id();
            $table->enum('action', ['bet', 'fold', 'check', 'call', 'raise', 'allin']);
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(RoomRound::class, 'room_round_id');
            $table->enum('round_phase', ['pre_flop', 'flop', 'turn', 'river']);
            $table->unsignedBigInteger('amount')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('round_actions');
    }
};
