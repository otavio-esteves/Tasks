<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskChecklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskChecklist>
 */
class TaskChecklistFactory extends Factory
{
    protected $model = TaskChecklist::class;

    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'label' => fake()->sentence(4),
            'is_completed' => fake()->boolean(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
