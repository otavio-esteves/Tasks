<?php

namespace Database\Seeders;

use App\Models\Secretariat;
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
        $nomeSecretaria = 'Secretaria de Urbanismo';

        $secretaria = Secretariat::updateOrCreate(
            ['name' => $nomeSecretaria],
            ['slug' => Str::slug($nomeSecretaria)]
        );

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'secretariat_id' => $secretaria->id,
            'email_verified_at' => now(),
        ]);
    }
}
