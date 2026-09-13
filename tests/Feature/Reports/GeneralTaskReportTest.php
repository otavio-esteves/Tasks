<?php

namespace Tests\Feature\Reports;

use App\Application\Tasks\Queries\GetGeneralTaskReport;
use App\Domain\Tasks\TaskStatus;
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
            'due_date' => '2026-09-15',
        ]);
        $included->assignees()->attach($assignee);
        Task::factory()->forTeam($team)->create([
            'title' => 'Tarefa fora do período',
            'due_date' => '2026-10-15',
        ])->assignees()->attach($assignee);

        $report = app(GetGeneralTaskReport::class)->handle($team->id, [
            'assignee_id' => $assignee->id,
            'due_from' => '2026-09-01',
            'due_to' => '2026-09-30',
        ]);

        $this->assertSame(1, $report->summary['total']);
        $this->assertSame(1, $report->summary['pending']);
        $this->assertSame(1, $report->summary['urgent']);

        $this->actingAs($user)
            ->get(route('teams.reports.general', [
                'team' => $team,
                'assignee_id' => $assignee->id,
                'due_from' => '2026-09-01',
                'due_to' => '2026-09-30',
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

    public function test_user_cannot_print_report_from_another_team(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($ownTeam)->create();

        $this->actingAs($user)
            ->get(route('teams.reports.general', $otherTeam))
            ->assertForbidden();
    }
}
