<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RoomTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
//        $redis = Redis::connection()->client();
//        $redis->flushDB();
        Redis::flushDB();
    }

    public function testCreateRoom()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->post('api/rooms');
        $data = $response->json();
        $this->assertDatabaseHas('rooms', ['user_id' => $user->id, 'id' => $data['id']]);
        $response->assertStatus(201);
    }

    public function testJoiningARoom()
    {
        $user = User::factory()->create();
        $user2 = User::factory()->create();
        $this->actingAs($user);
        $response = $this->post('api/rooms');
        $data = $response->json();
        $this->assertDatabaseHas('rooms', ['user_id' => $user->id, 'id' => $data['id']]);
        $response->assertStatus(201);

        $this->actingAs($user2);
        $this->put('api/rooms/'.$data['id'].'/join');

    }
}
