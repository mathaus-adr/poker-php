<?php

namespace App\Http\Controllers;

use App\Commands\CommandExecutionData;
use App\Domains\Game\Room\Commands\CreateRoom;
use App\Domains\Game\Room\Commands\JoinRoom;
use App\Domains\Game\StartPokerGame;
use App\Models\Room;

class RoomController extends Controller
{
    public function create(CreateRoom $createRoom)
    {
        return $createRoom->execute()->getData()['room'];
    }

    /**
     * @param  Room  $room
     * @param  JoinRoom  $joinRoom
     * @return mixed
     */
    public function join($id, JoinRoom $joinRoom)
    {
        return $joinRoom->execute(new CommandExecutionData([
            'user' => auth()->user(),
            'room' => Room::find($id)
        ]))->getData()['room'];
    }

    public function startGame($id, StartPokerGame $startGame)
    {
        return $startGame->execute(new CommandExecutionData([
            'room' => Room::find($id)
        ]))->getData();
    }

//    public function executeGameAction($id, PokerGameAction $pokerGameAction)
//    {
//        return $pokerGameAction->execute(new CommandExecutionData([
//            'room' => Room::find($id)
//        ]))->getData();
//    }
}
