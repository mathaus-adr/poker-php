<?php

namespace App\Http\Middleware;

use App\Models\Room;
use App\Models\RoomUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlayerTurn
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $room = RoomUser::where(['user_id' => $user->id])->first();

        if ($room->data['current_player_to_bet']['id'] === $user->id) {
            return $next($request);
        }

        dd('teste');
    }
}
