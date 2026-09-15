<?php

namespace Database\Seeders;

use App\Domain\Tasks\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
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

        $user = User::query()->firstOrNew([
            'email' => 'test@example.com',
        ]);

        $user->forceFill([
            'name' => 'Test User',
            'password' => Hash::make('password'),
            'team_id' => $equipe->id,
            'email_verified_at' => now(),
        ])->save();

        $category = Category::updateOrCreate(
            [
                'team_id' => $equipe->id,
                'slug' => 'indicadores',
            ],
            [
                'name' => 'Indicadores',
                'description' => 'Tarefas de demonstração para os indicadores.',
            ],
        );

        $currentYear = now()->year;
        $tasks = [
            ['Planejamento anual', $currentYear - 2, 11, 15, 10],
            ['Revisão anual', $currentYear - 1, 7, 10, 7],
            ['Manutenção de janeiro', $currentYear, 1, 8, 5],
            ['Vistoria de março', $currentYear, 3, 12, 3],
            ['Inventário de abril', $currentYear, 4, 22, 6],
            ['Atualização de maio', $currentYear, 5, 5, 10],
            ['Inspeção de junho', $currentYear, 6, 18, 4],
            ['Reparo prioritário', $currentYear, 9, 3, 2],
            ['Conferência operacional', $currentYear, 9, 8, 5],
            ['Treinamento de outubro', $currentYear, 10, 14, 8],
            ['Fechamento de dezembro', $currentYear, 12, 2, 12],
        ];

        foreach ($tasks as [$title, $year, $month, $day, $duration]) {
            $startDate = now()->setDate($year, $month, $day)->startOfDay();

            Task::updateOrCreate(
                [
                    'team_id' => $equipe->id,
                    'title' => $title,
                ],
                [
                    'category_id' => $category->id,
                    'location' => 'Operações',
                    'observation' => 'Tarefa de demonstração para os indicadores.',
                    'start_date' => $startDate->toDateString(),
                    'due_date' => $startDate->copy()->addDays($duration)->toDateString(),
                    'is_urgent' => $title === 'Reparo prioritário',
                    'status' => TaskStatus::Pending,
                ],
            );
        }

        foreach (range(1, 45) as $index) {
            $startDate = now()->startOfYear()->addDays(($index - 1) * 7);
            $title = sprintf('Indicador periódico %02d', $index);

            Task::updateOrCreate(
                [
                    'team_id' => $equipe->id,
                    'title' => $title,
                ],
                [
                    'category_id' => $category->id,
                    'location' => 'Operações',
                    'observation' => 'Tarefa de demonstração para os indicadores.',
                    'start_date' => $startDate->toDateString(),
                    'due_date' => $startDate->copy()->addDays(2 + ($index % 8))->toDateString(),
                    'is_urgent' => $index % 9 === 0,
                    'status' => TaskStatus::Pending,
                ],
            );
        }
    }
}
