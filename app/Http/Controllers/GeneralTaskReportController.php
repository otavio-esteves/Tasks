<?php

namespace App\Http\Controllers;

use App\Application\Tasks\Queries\GetGeneralTaskReport;
use App\Application\Users\Queries\ListTeamUsers;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class GeneralTaskReportController extends Controller
{
    public function __invoke(
        Request $request,
        Team $team,
        GetGeneralTaskReport $getGeneralTaskReport,
        ListTeamUsers $listTeamUsers,
    ) {
        Gate::authorize('viewAny', [Task::class, $team]);

        $validated = $request->validate([
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_from' => ['nullable', 'date_format:Y-m-d'],
            'due_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:due_from'],
            'chart_style' => ['nullable', 'in:lines,columns,pie'],
        ]);

        $users = $listTeamUsers->handle($team->id);
        $assigneeId = isset($validated['assignee_id']) ? (int) $validated['assignee_id'] : null;

        if ($assigneeId !== null && ! $users->contains('id', $assigneeId)) {
            abort(404);
        }

        return view('reports.general', [
            'team' => $team,
            'report' => $getGeneralTaskReport->handle($team->id, [
                'assignee_id' => $assigneeId,
                'due_from' => $validated['due_from'] ?? null,
                'due_to' => $validated['due_to'] ?? null,
            ]),
            'assignee' => $assigneeId === null ? null : $users->firstWhere('id', $assigneeId),
            'dueFrom' => $validated['due_from'] ?? null,
            'dueTo' => $validated['due_to'] ?? null,
            'chartStyle' => $validated['chart_style'] ?? 'lines',
        ]);
    }
}
