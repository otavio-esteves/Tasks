<?php

namespace App\Application\Tasks;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Tasks\Data\UpdateTaskData;
use App\Application\Tasks\Validators\EnsureCategoryBelongsToTeam;
use App\Models\Task;

class UpdateTask
{
    public function __construct(
        private readonly EnsureCategoryBelongsToTeam $ensureCategoryBelongsToTeam,
        private readonly GetTask $getTask,
        private readonly TaskRepository $tasks,
    ) {}

    public function handle(int $teamId, int $userId, int $taskId, UpdateTaskData $data): Task
    {
        $this->ensureCategoryBelongsToTeam->handle($teamId, $data->categoryId);

        $task = $this->getTask->handle($teamId, $taskId);

        return $this->tasks->update($task, $userId, $data);
    }
}
