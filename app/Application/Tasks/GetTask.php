<?php

namespace App\Application\Tasks;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Domain\Tasks\Exceptions\TaskNotFound;
use App\Models\Task;

class GetTask
{
    public function __construct(
        private readonly TaskRepository $tasks,
    ) {}

    public function handle(int $teamId, int $taskId): Task
    {
        $task = $this->tasks->findByIdForTeam($teamId, $taskId);

        if (! $task) {
            throw new TaskNotFound;
        }

        return $task;
    }
}
