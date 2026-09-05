<?php

namespace App\Application\Tasks;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Tasks\Data\CreateTaskData;
use App\Application\Tasks\Validators\EnsureCategoryBelongsToTeam;
use App\Models\Task;

class CreateTask
{
    public function __construct(
        private readonly EnsureCategoryBelongsToTeam $ensureCategoryBelongsToTeam,
        private readonly TaskRepository $tasks,
    ) {}

    public function handle(int $teamId, int $userId, CreateTaskData $data): Task
    {
        $this->ensureCategoryBelongsToTeam->handle($teamId, $data->categoryId);

        return $this->tasks->createForTeam($teamId, $userId, $data);
    }
}
