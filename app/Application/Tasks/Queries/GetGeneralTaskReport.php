<?php

namespace App\Application\Tasks\Queries;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Tasks\Data\TaskReportResult;

class GetGeneralTaskReport
{
    public function __construct(private readonly TaskRepository $tasks) {}

    /**
     * @param  array{assignee_id?:int|null,due_from?:string|null,due_to?:string|null}  $filters
     */
    public function handle(int $teamId, array $filters = []): TaskReportResult
    {
        return $this->tasks->reportForTeam($teamId, $filters);
    }
}
