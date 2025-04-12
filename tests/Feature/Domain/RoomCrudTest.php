<?php

use App\Domains\Game\Room\Actions\CreateRoom;
use App\Domains\Game\Room\Actions\LeaveRoom;
use App\Events\RoomListUpdatedEvent;
use App\Models\User;

describe('room operations tests', function () {
    it('should create a room or last user leaving the room and dispatch RoomListUpdatedEvent', function () {
        $user = User::factory()->create();

        Event::fakeFor(function () use ($user) {
            $room = app(CreateRoom::class)->execute($user);
            app(LeaveRoom::class)->execute($room,$user);
            Event::assertDispatchedTimes(RoomListUpdatedEvent::class, 2);
        });
    });
})->group('rooms');
