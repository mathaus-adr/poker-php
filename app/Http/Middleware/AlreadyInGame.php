<?php

namespace App\Http\Middleware;

use App\Models\Room;
use App\Models\RoomUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AlreadyInGame
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        $room = RoomUser::where(['user_id' => $user->id])->first();
        if ($room) {
            return redirect('room/'.$room->room_id);
        }

        return $next($request);
    }
}
