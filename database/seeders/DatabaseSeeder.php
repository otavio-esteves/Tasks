<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $nomeEquipe = 'Operações';

        $equipe = Team::updateOrCreate(
            ['name' => $nomeEquipe],
            ['slug' => Str::slug($nomeEquipe)]
        );

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'team_id' => $equipe->id,
            'email_verified_at' => now(),
        ]);
    }
}
