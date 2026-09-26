<?php

namespace Tests\Feature\Reports;

use App\Application\Tasks\Queries\GetGeneralTaskReport;
use App\Domain\Tasks\Exceptions\TaskReportTooLarge;
use App\Domain\Tasks\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralTaskReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_user_can_print_filtered_general_report(): void
    {
        $team = Team::factory()->create(['name' => 'Equipe Relatório']);
        $user = User::factory()->forTeam($team)->create();
        $assignee = User::factory()->forTeam($team)->create(['name' => 'Ana Responsável']);
        $included = Task::factory()->forTeam($team)->urgent()->create([
            'title' => 'Tarefa incluída',
            'status' => TaskStatus::Pending,
            'start_date' => '2026-09-10',
            'due_date' => '2026-09-15',
        ]);
        $included->assignees()->attach($assignee);
        Task::factory()->forTeam($team)->create([
            'title' => 'Tarefa fora do período',
            'start_date' => '2026-10-01',
            'due_date' => '2026-10-15',
        ])->assignees()->attach($assignee);

        $report = app(GetGeneralTaskReport::class)->handle($team->id, [
            'assignee_id' => $assignee->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]);

        $this->assertSame(1, $report->summary['total']);
        $this->assertSame(1, $report->summary['pending']);
        $this->assertSame(1, $report->summary['urgent']);

        $this->actingAs($user)
            ->get(route('teams.reports.general', [
                'team' => $team,
                'assignee_id' => $assignee->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-30',
                'chart_style' => 'pie',
            ]))
            ->assertOk()
            ->assertSee('Relatório geral de tarefas')
            ->assertSee('Ana Responsável')
            ->assertSee('Tarefa incluída')
            ->assertDontSee('Tarefa fora do período')
            ->assertSee('Imprimir ou salvar PDF')
            ->assertSee('estilo pie')
            ->assertSee('print-color-adjust: exact');

        foreach (['lines', 'columns', 'pie'] as $chartStyle) {
            $this->actingAs($user)
                ->get(route('teams.reports.general', ['team' => $team, 'chart_style' => $chartStyle]))
                ->assertOk()
                ->assertSee("estilo {$chartStyle}");
        }
    }

    public function test_report_includes_tasks_that_overlap_the_selected_period(): void
    {
        $team = Team::factory()->create();
        $spanningTask = Task::factory()->forTeam($team)->create([
            'title' => 'Tarefa em andamento no período',
            'start_date' => '2026-08-20',
            'due_date' => '2026-10-10',
        ]);
        Task::factory()->forTeam($team)->create([
            'title' => 'Tarefa encerrada antes do período',
            'start_date' => '2026-08-01',
            'due_date' => '2026-08-31',
        ]);

        $report = app(GetGeneralTaskReport::class)->handle($team->id, [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]);

        $this->assertSame([$spanningTask->id], $report->tasks->pluck('id')->all());
    }

    public function test_report_summary_counts_status_urgency_and_overdue_in_the_team(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $category = Category::factory()->for($team)->create();

        Task::factory()->forCategory($category)->create([
            'status' => TaskStatus::Pending,
            'is_urgent' => true,
            'due_date' => now()->subDay()->toDateString(),
        ]);
        Task::factory()->forCategory($category)->create([
            'status' => TaskStatus::InProgress,
            'is_urgent' => false,
            'due_date' => now()->addDay()->toDateString(),
        ]);
        Task::factory()->forCategory($category)->create([
            'status' => TaskStatus::Completed,
            'is_urgent' => true,
            'due_date' => now()->subDay()->toDateString(),
        ]);
        Task::factory()->forTeam($otherTeam)->create(['is_urgent' => true]);

        $report = app(GetGeneralTaskReport::class)->handle($team->id);

        $this->assertSame([
            'total' => 3,
            'pending' => 1,
            'in_progress' => 1,
            'completed' => 1,
            'urgent' => 1,
            'overdue' => 1,
        ], $report->summary);
    }

    public function test_empty_report_has_zeroed_summary(): void
    {
        $team = Team::factory()->create();

        $report = app(GetGeneralTaskReport::class)->handle($team->id);

        $this->assertCount(0, $report->tasks);
        $this->assertSame(0, $report->summary['total']);
        $this->assertSame(0, $report->summary['overdue']);
    }

    public function test_user_cannot_print_report_from_another_team(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($ownTeam)->create();

        $this->actingAs($user)
            ->get(route('teams.reports.general', $otherTeam))
            ->assertForbidden();
    }

    public function test_report_rejects_more_than_500_tasks_and_shows_filter_guidance(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        $category = Category::factory()->for($team)->create();
        Task::factory()->forCategory($category)->count(500)->create([
            'start_date' => '2026-01-01',
            'due_date' => '2026-01-15',
        ]);
        Task::factory()->forCategory($category)->create([
            'start_date' => '2026-02-01',
            'due_date' => '2026-02-15',
        ]);
        Task::factory()->forTeam($otherTeam)->create();

        $filtered = app(GetGeneralTaskReport::class)->handle($team->id, [
            'end_date' => '2026-01-31',
        ]);
        $this->assertCount(500, $filtered->tasks);
        $this->assertSame(500, $filtered->summary['total']);

        $this->actingAs($user)
            ->get(route('teams.reports.general', $team))
            ->assertStatus(422)
            ->assertSee('O relatório contém mais de 500 tarefas')
            ->assertSee('Voltar às tarefas');

        $this->expectException(TaskReportTooLarge::class);
        app(GetGeneralTaskReport::class)->handle($team->id);
    }
}
