<?php

namespace App\Application\Dashboard\Queries;

use App\Application\Categories\Contracts\CategoryRepository;
use App\Application\Dashboard\Data\DashboardCountersData;
use App\Application\Tasks\Contracts\TaskRepository;
use App\Application\Teams\Contracts\TeamRepository;

class GetDashboardCounters
{
    public function __construct(
        private readonly TeamRepository $teams,
        private readonly CategoryRepository $categories,
        private readonly TaskRepository $tasks,
    ) {}

    public function handle(): DashboardCountersData
    {
        return new DashboardCountersData(
            activeTeams: $this->teams->countActive(),
            activeCategories: $this->categories->countActive(),
            activePendingTasks: $this->tasks->countActivePending(),
        );
    }
}
