<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class StartPokerGameTest extends TestCase
{
    use RefreshDatabase;
    public function setUp(): void
    {
        parent::setUp();
        Redis::flushdb();
    }
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $user = User::factory(5)->create();
        $this->actingAs($user[0]);
        $response = $this->post('api/rooms');
        $data = $response->json();
        $this->assertDatabaseHas('rooms', ['user_id' => $user[0]->id, 'id' => $data['id']]);
        $response->assertStatus(201);

        $this->actingAs($user[1]);
        $this->put('api/rooms/'.$data['id'].'/join');
        $this->actingAs($user[2]);
        $this->put('api/rooms/'.$data['id'].'/join');
        $this->actingAs($user[3]);
        $this->put('api/rooms/'.$data['id'].'/join');
        $this->actingAs($user[4]);
        $this->put('api/rooms/'.$data['id'].'/join');
        $this->actingAs($user[0]);
        $this->post('api/rooms/'.$data['id'].'/start');
    }
}
