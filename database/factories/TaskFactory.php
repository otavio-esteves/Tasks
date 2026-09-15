<?php

namespace Database\Factories;

use App\Domain\Tasks\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $team = Team::factory();
        $startDate = fake()->date();

        return [
            'title' => fake()->sentence(3),
            'location' => fake()->address(),
            'observation' => fake()->sentence(),
            'start_date' => $startDate,
            'due_date' => fake()->dateTimeBetween($startDate, '+1 year')->format('Y-m-d'),
            'is_urgent' => fake()->boolean(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'team_id' => $team,
            'category_id' => Category::factory()->state([
                'team_id' => $team,
            ]),
        ];
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn () => [
            'team_id' => $team->id,
            'category_id' => Category::factory()->state([
                'team_id' => $team->id,
            ]),
        ]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn () => [
            'team_id' => $category->team_id,
            'category_id' => $category->id,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => [
            'is_urgent' => true,
        ]);
    }

    public function withStatus(TaskStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
        ]);
    }
}
