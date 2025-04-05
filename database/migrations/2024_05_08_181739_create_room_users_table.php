<?php

use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('room_users', function (Blueprint $table) {
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(Room::class, 'room_id');
            $table->unique(['room_id', 'user_id']);
            $table->json('user_info')->nullable();
            $table->enum('status', ['active', 'inactive', 'rejoin'])->default('active');
            $table->unsignedBigInteger('cash')->default(1000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_users');
    }
};
