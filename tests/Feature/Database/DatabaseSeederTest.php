<?php

namespace Tests\Feature\Database;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_provisions_the_documented_development_account_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $team = Team::query()->where('name', 'Operações')->firstOrFail();
        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame(1, User::query()->where('email', 'test@example.com')->count());
        $this->assertSame($team->id, $user->team_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password', $user->password));

        $demoTasks = Task::query()
            ->where('team_id', $team->id)
            ->where('observation', 'Tarefa de demonstração para os indicadores.')
            ->get();

        $this->assertCount(56, $demoTasks);
        $this->assertTrue($demoTasks->every(
            fn (Task $task): bool => $task->start_date !== null
                && $task->due_date !== null
                && $task->due_date->greaterThanOrEqualTo($task->start_date),
        ));
    }
}
