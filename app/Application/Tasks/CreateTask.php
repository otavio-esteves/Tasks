<?php

namespace App\Application\Tasks;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Tasks\Data\CreateTaskData;
use App\Application\Tasks\Validators\EnsureAssigneesBelongToTeam;
use App\Application\Tasks\Validators\EnsureCategoryBelongsToTeam;
use App\Models\Task;

class CreateTask
{
    public function __construct(
        private readonly EnsureCategoryBelongsToTeam $ensureCategoryBelongsToTeam,
        private readonly EnsureAssigneesBelongToTeam $ensureAssigneesBelongToTeam,
        private readonly TaskRepository $tasks,
    ) {}

    public function handle(int $teamId, int $userId, CreateTaskData $data): Task
    {
        $this->ensureCategoryBelongsToTeam->handle($teamId, $data->categoryId);
        $this->ensureAssigneesBelongToTeam->handle($teamId, $data->assigneeIds);

        return $this->tasks->createForTeam($teamId, $userId, $data);
    }
}
