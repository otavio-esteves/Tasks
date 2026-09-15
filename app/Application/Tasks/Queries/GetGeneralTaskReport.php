<?php

namespace App\Application\Tasks\Queries;

use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Tasks\Data\TaskReportResult;

class GetGeneralTaskReport
{
    public function __construct(private readonly TaskRepository $tasks) {}

    /**
     * @param  array{assignee_id?:int|null,start_date?:string|null,end_date?:string|null}  $filters
     */
    public function handle(int $teamId, array $filters = []): TaskReportResult
    {
        return $this->tasks->reportForTeam($teamId, $filters);
    }
}
