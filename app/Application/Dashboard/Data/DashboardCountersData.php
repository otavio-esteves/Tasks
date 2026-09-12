<?php

namespace App\Application\Dashboard\Data;

final readonly class DashboardCountersData
{
    public function __construct(
        public int $activeTeams,
        public int $activeCategories,
        public int $activePendingTasks,
    ) {}
}
