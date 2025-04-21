<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Room::class, 'room_id');
            $table->foreignIdFor(User::class, 'player_turn_id')->nullable();
            $table->uuid('play_identifier')->nullable();
            $table->foreignIdFor(User::class, 'dealer_id')->nullable();
            $table->foreignIdFor(User::class, 'big_blind_id')->nullable();
            $table->foreignIdFor(User::class, 'small_blind_id')->nullable();
            $table->integer('total_players_in_round')->nullable();
            $table->unsignedBigInteger('total_pot')->nullable();
            $table->unsignedBigInteger('current_bet_amount_to_join')->nullable();
            $table->foreignIdFor(User::class, 'winner_id')->nullable();
            $table->enum('phase', ['pre_flop', 'flop', 'turn', 'river', 'end'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_rounds');
    }
};
