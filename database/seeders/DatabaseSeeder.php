<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

         \App\Models\User::factory()->create([
             'name' => 'MATHAUS1',
             'email' => 'test@example.com',
             'password' => Hash::make('123')
         ]);
         \App\Models\User::factory()->create([
             'name' => 'MATHAUS2',
             'email' => 'test2@example.com',
             'password' => Hash::make('123')
         ]);
         \App\Models\User::factory()->create([
             'name' => 'MATHAUS3',
             'email' => 'test3@example.com',
             'password' => Hash::make('123')
         ]);
    }
}
