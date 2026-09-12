<?php

namespace App\Application\Tasks;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Domain\Tasks\TaskStatus;
use App\Models\Task;

class ChangeTaskStatus
{
    public function __construct(
        private readonly GetTask $getTask,
        private readonly TaskRepository $tasks,
    ) {}

    public function handle(int $teamId, int $userId, int $taskId, TaskStatus $status): Task
    {
        $task = $this->getTask->handle($teamId, $taskId);

        if ($task->status === $status) {
            return $task;
        }

        return $this->tasks->changeStatus($task, $userId, $status);
    }
}
