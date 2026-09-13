<?php

namespace App\Application\Tasks\Contracts;

use App\Application\Tasks\Data\CreateTaskData;
use App\Application\Tasks\Data\TaskListResult;
use App\Application\Tasks\Data\TaskReportResult;
use App\Application\Tasks\Data\UpdateTaskData;
use App\Domain\Tasks\TaskStatus;
use App\Models\Task;

interface TaskRepository
{
    public function createForTeam(int $teamId, int $userId, CreateTaskData $data): Task;

    public function findByIdForTeam(int $teamId, int $taskId): ?Task;

    public function update(Task $task, int $userId, UpdateTaskData $data): Task;

    public function changeStatus(Task $task, int $userId, TaskStatus $status): Task;

    public function delete(Task $task): void;

    /**
     * @param  array{category_id?:int|null,assignee_id?:int|null,status?:string|null,urgent?:bool|null,quick_filter?:string|null}  $filters
     */
    public function listForTeam(int $teamId, string $search = '', array $filters = [], int $perPage = 15): TaskListResult;

    /**
     * @param  array{assignee_id?:int|null,due_from?:string|null,due_to?:string|null}  $filters
     */
    public function reportForTeam(int $teamId, array $filters = []): TaskReportResult;
}
