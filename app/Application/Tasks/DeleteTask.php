<?php

namespace App\Application\Tasks;

use App\Application\Tasks\Contracts\TaskRepository;

class DeleteTask
{
    public function __construct(
        private readonly GetTask $getTask,
        private readonly TaskRepository $tasks,
    ) {}

    public function handle(int $teamId, int $taskId): void
    {
        $task = $this->getTask->handle($teamId, $taskId);

        $this->tasks->delete($task);
    }
}
