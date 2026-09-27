<?php

namespace Tests\Feature\Tasks;

use App\Application\Tasks\Queries\ListMyTasks;
use App\Domain\Tasks\TaskStatus;
use App\Livewire\Team\TaskManager;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MyWorkTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_work_lists_only_tasks_assigned_to_user_in_their_teams(): void
    {
        $primaryTeam = Team::factory()->create();
        $additionalTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($primaryTeam)->create();
        $user->additionalTeams()->attach($additionalTeam);

        $primaryTask = Task::factory()->forTeam($primaryTeam)->create(['title' => 'Minha tarefa principal', 'status' => TaskStatus::Pending]);
        $additionalTask = Task::factory()->forTeam($additionalTeam)->create(['title' => 'Minha tarefa adicional', 'status' => TaskStatus::Completed]);
        $unassignedTask = Task::factory()->forTeam($primaryTeam)->create(['title' => 'Tarefa de outra pessoa']);
        $inaccessibleTask = Task::factory()->forTeam($otherTeam)->create(['title' => 'Tarefa fora das equipes']);

        $primaryTask->assignees()->attach($user);
        $additionalTask->assignees()->attach($user);
        $inaccessibleTask->assignees()->attach($user);

        $listing = app(ListMyTasks::class)->handle($user);

        $this->assertEqualsCanonicalizing([$primaryTask->id, $additionalTask->id], $listing->getCollection()->pluck('id')->all());
        $this->assertSame(2, $listing->total());

        $this->actingAs($user)
            ->get(route('teams.tasks', $primaryTeam).'?view=my-work')
            ->assertOk()
            ->assertSee('data-testid="my-work-view-trigger"', false)
            ->assertSee('Minha tarefa principal')
            ->assertSee('Minha tarefa adicional')
            ->assertDontSee('Tarefa fora das equipes');

        $this->actingAs($user)->get(route('teams.tasks', $otherTeam))->assertForbidden();
    }

    public function test_my_work_requires_authentication(): void
    {
        $team = Team::factory()->create();

        $this->get(route('teams.tasks', $team).'?view=my-work')->assertRedirect(route('login'));
    }

    public function test_assigned_tasks_open_in_the_editor_and_can_be_updated_from_my_work(): void
    {
        $primaryTeam = Team::factory()->create();
        $additionalTeam = Team::factory()->create();
        $user = User::factory()->forTeam($primaryTeam)->create();
        $user->additionalTeams()->attach($additionalTeam);
        $task = Task::factory()->forTeam($additionalTeam)->create(['title' => 'Tarefa atribuída']);
        $task->assignees()->attach($user);

        $this->actingAs($user)
            ->get(route('teams.tasks', $primaryTeam).'?view=my-work')
            ->assertOk()
            ->assertSee('data-testid="my-work-task-'.$task->id.'"', false)
            ->assertSee(route('teams.tasks', $additionalTeam).'?view=my-work&amp;task='.$task->id, false)
            ->assertSee('$wire.edit(requestedTask)', false)
            ->assertSeeInOrder(['data-testid="my-work-view-trigger"', "setPanelView('tasks')"], false);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $additionalTeam])
            ->call('edit', $task->id)
            ->assertSet('form.taskId', $task->id)
            ->assertDispatched('open-task-modal')
            ->set('form.title', 'Tarefa atualizada em Meu Trabalho')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('task-saved');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Tarefa atualizada em Meu Trabalho']);
    }

    public function test_my_work_filters_multiple_statuses_without_exposing_other_tasks(): void
    {
        $primaryTeam = Team::factory()->create();
        $additionalTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($primaryTeam)->create(['name' => 'Usuário de teste']);
        $user->additionalTeams()->attach($additionalTeam);

        $pending = Task::factory()->forTeam($primaryTeam)->create(['status' => TaskStatus::Pending]);
        $inProgress = Task::factory()->forTeam($additionalTeam)->create(['status' => TaskStatus::InProgress]);
        $completed = Task::factory()->forTeam($primaryTeam)->create(['status' => TaskStatus::Completed]);
        $unassigned = Task::factory()->forTeam($primaryTeam)->create(['status' => TaskStatus::Pending]);
        $inaccessible = Task::factory()->forTeam($otherTeam)->create(['status' => TaskStatus::Completed]);

        foreach ([$pending, $inProgress, $completed, $inaccessible] as $task) {
            $task->assignees()->attach($user);
        }

        $filtered = app(ListMyTasks::class)->handle($user, statuses: [TaskStatus::Pending->value, TaskStatus::Completed->value]);

        $this->assertEqualsCanonicalizing([$pending->id, $completed->id], $filtered->getCollection()->pluck('id')->all());
        $this->assertSame(2, $filtered->total());

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $primaryTeam])
            ->assertSee('Meu Trabalho <span class="font-normal text-muted-foreground">/ Usuário de teste</span>', false)
            ->assertSee('data-testid="my-work-status-select"', false)
            ->assertSee('aria-controls="my-work-status-options"', false)
            ->assertSee('aria-label="Filtrar meu trabalho por status; selecione um ou mais"', false)
            ->call('toggleMyWorkStatus', TaskStatus::Pending->value)
            ->assertSet('myWorkStatuses', [TaskStatus::Pending->value])
            ->assertSee('data-testid="my-work-task-'.$pending->id.'"', false)
            ->assertDontSee('data-testid="my-work-task-'.$inProgress->id.'"', false)
            ->assertDontSee('data-testid="my-work-task-'.$unassigned->id.'"', false)
            ->assertDontSee('data-testid="my-work-task-'.$inaccessible->id.'"', false)
            ->call('toggleMyWorkStatus', TaskStatus::Completed->value)
            ->assertSet('myWorkStatuses', [TaskStatus::Pending->value, TaskStatus::Completed->value])
            ->assertSee('2 status selecionados')
            ->assertSee('data-testid="my-work-task-'.$completed->id.'"', false)
            ->call('toggleMyWorkStatus', TaskStatus::Pending->value)
            ->assertSet('myWorkStatuses', [TaskStatus::Completed->value])
            ->assertDontSee('data-testid="my-work-task-'.$pending->id.'"', false)
            ->call('clearMyWorkStatuses')
            ->assertSet('myWorkStatuses', [])
            ->assertSee('data-testid="my-work-task-'.$inProgress->id.'"', false);
    }

    public function test_assigned_task_status_can_be_advanced_directly_from_my_work_across_teams(): void
    {
        $primaryTeam = Team::factory()->create();
        $additionalTeam = Team::factory()->create();
        $user = User::factory()->forTeam($primaryTeam)->create();
        $user->additionalTeams()->attach($additionalTeam);
        $task = Task::factory()->forTeam($additionalTeam)->create(['status' => TaskStatus::Pending]);
        $task->assignees()->attach($user);

        $component = Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $primaryTeam])
            ->assertSee('data-testid="my-work-status-'.$task->id.'"', false)
            ->assertSee('wire:click="cycleMyWorkStatus('.$additionalTeam->id.', '.$task->id.')"', false)
            ->call('cycleMyWorkStatus', $additionalTeam->id, $task->id)
            ->assertDispatched('task-status-updated');

        $this->assertSame(TaskStatus::InProgress, $task->refresh()->status);

        $component->call('cycleMyWorkStatus', $additionalTeam->id, $task->id)
            ->assertDispatched('task-status-updated');
        $this->assertSame(TaskStatus::Completed, $task->refresh()->status);

        $component->call('cycleMyWorkStatus', $additionalTeam->id, $task->id)
            ->assertDispatched('task-status-updated');
        $this->assertSame(TaskStatus::Pending, $task->refresh()->status);
        $this->assertDatabaseCount('task_histories', 3);
    }

    public function test_my_work_status_action_rejects_unassigned_and_inaccessible_tasks(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        $unassigned = Task::factory()->forTeam($team)->create(['status' => TaskStatus::Pending]);
        $inaccessible = Task::factory()->forTeam($otherTeam)->create(['status' => TaskStatus::Pending]);
        $inaccessible->assignees()->attach($user);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('cycleMyWorkStatus', $team->id, $unassigned->id)
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('cycleMyWorkStatus', $otherTeam->id, $inaccessible->id)
            ->assertForbidden();

        $this->assertSame(TaskStatus::Pending, $unassigned->refresh()->status);
        $this->assertSame(TaskStatus::Pending, $inaccessible->refresh()->status);
        $this->assertDatabaseCount('task_histories', 0);
    }
}
