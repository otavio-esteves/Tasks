<?php

namespace App\Application\Tasks\Queries;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Domain\Tasks\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListMyTasks
{
    public function __construct(private readonly TaskRepository $tasks) {}

    /**
     * @param  array<mixed>  $statuses
     * @return LengthAwarePaginator<int, Task>
     */
    public function handle(User $user, int $perPage = 15, array $statuses = []): LengthAwarePaginator
    {
        $validStatuses = array_values(array_unique(array_filter(
            $statuses,
            static fn (mixed $status): bool => is_string($status) && TaskStatus::tryFrom($status) !== null,
        )));

        return $this->tasks->listAssignedToUser($user, $perPage, $validStatuses);
    }
}
