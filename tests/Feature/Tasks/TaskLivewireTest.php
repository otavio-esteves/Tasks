<?php

namespace Tests\Feature\Tasks;

use App\Domain\Tasks\TaskStatus;
use App\Livewire\Team\TaskManager;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_task_manager(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)
            ->get(route('teams.tasks', $team))
            ->assertOk()
            ->assertSee($team->name);
    }

    public function test_team_sidebar_lists_all_teams_without_granting_cross_team_navigation(): void
    {
        $ownTeam = Team::factory()->create(['name' => 'Equipe do usuário']);
        $otherTeam = Team::factory()->create(['name' => 'Equipe sem acesso']);
        $user = User::factory()->forTeam($ownTeam)->create();

        $this->actingAs($user)
            ->get(route('teams.tasks', $ownTeam))
            ->assertOk()
            ->assertSee('Equipes')
            ->assertSee('data-testid="teams-menu-trigger"', false)
            ->assertSee('x-collapse', false)
            ->assertSee($ownTeam->name)
            ->assertSee($otherTeam->name)
            ->assertSee(route('teams.tasks', $ownTeam), false)
            ->assertDontSee(route('teams.tasks', $otherTeam), false)
            ->assertSee('Sem acesso')
            ->assertDontSee('data-testid="admin-system-menu-trigger"', false);
    }

    public function test_admin_sidebar_links_all_teams_and_exposes_system_settings(): void
    {
        $currentTeam = Team::factory()->create(['name' => 'Equipe atual']);
        $otherTeam = Team::factory()->create(['name' => 'Outra equipe']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('teams.tasks', $currentTeam))
            ->assertOk()
            ->assertSee('data-testid="admin-system-menu-trigger"', false)
            ->assertSee('data-testid="admin-system-settings-dialog"', false)
            ->assertDontSee('x-on:click="systemOpen = !systemOpen"', false)
            ->assertSee('Configurações administrativas')
            ->assertSee(route('teams.tasks', $currentTeam), false)
            ->assertSee(route('teams.tasks', $otherTeam), false)
            ->assertSee('Gerencie as equipes do sistema.');
    }

    public function test_admin_can_switch_the_system_settings_section(): void
    {
        $team = Team::factory()->create();
        $admin = User::factory()->admin()->create();

        $component = Livewire::actingAs($admin)
            ->test(TaskManager::class, ['team' => $team])
            ->assertSet('systemTab', 'teams')
            ->assertSee('Nova equipe')
            ->call('selectSystemTab', 'categories')
            ->assertSet('systemTab', 'categories')
            ->assertSee('Nova categoria')
            ->assertDontSee('Nova equipe')
            ->call('selectSystemTab', 'users')
            ->assertSet('systemTab', 'users')
            ->assertSee('Cargos dos usuários')
            ->call('selectSystemTab', 'access')
            ->assertSet('systemTab', 'access')
            ->assertSee('Exigir identificação');
    }

    public function test_task_manager_renders_list_view_control_and_inline_checklist_panel(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        Task::factory()->forTeam($team)->create(['status' => TaskStatus::Pending]);

        $this->actingAs($user)
            ->get(route('teams.tasks', $team))
            ->assertOk()
            ->assertSee('Visualização em lista')
            ->assertSee('data-testid="indicators-view-trigger"', false)
            ->assertSee('data-testid="reports-view-trigger"', false)
            ->assertSee('data-testid="indicator-chart-controls"', false)
            ->assertSee("setPanelView('indicators')", false)
            ->assertSee("url.searchParams.set('view', view)", false)
            ->assertSee('Panorama operacional')
            ->assertSee('Linhas')
            ->assertSee('Colunas')
            ->assertSee('Pizza')
            ->assertSee('Pendentes')
            ->assertSee('Urgentes')
            ->assertSee('Tarefas dos indicadores')
            ->assertSee('Central de relatórios')
            ->assertSee('Relatório geral')
            ->assertSee('Imprimir PDF')
            ->assertSee('Estilo do gráfico')
            ->assertSee('aria-label="Abrir calendário da data inicial"', false)
            ->assertSee('Tarefas por período')
            ->assertSee('Gráfico de colunas com a quantidade de tarefas por período')
            ->assertSee('id="indicator-start-date"', false)
            ->assertSee('id="indicator-end-date"', false)
            ->assertSee('Agrupar por')
            ->assertSee('Diário')
            ->assertSee('Semanal')
            ->assertSee('Mensal')
            ->assertSee('Anual')
            ->assertSee('aria-label="Estilo do gráfico do relatório"', false)
            ->assertSee('ph-chart-pie-slice', false)
            ->assertSee('rounded-full px-2.5', false)
            ->assertSee('Todos os usuários')
            ->assertSee('Responsáveis')
            ->assertDontSee('wire:model.live="form.assigneeIds"', false)
            ->assertDontSee('<select', false)
            ->assertSee('data-testid="user-settings-dialog"', false)
            ->assertSee('data-testid="task-title-input"', false)
            ->assertSee('bg-muted/50', false)
            ->assertSee('h-[100dvh]', false)
            ->assertSee('w-[calc(100vw-1rem)]', false)
            ->assertSee('max-h-[calc(100dvh-1rem)]', false)
            ->assertSee('grid-cols-2 gap-4 sm:grid-cols-5', false)
            ->assertSee('p-3 sm:p-6', false)
            ->assertSee('Duplo clique para editar')
            ->assertSee('Clique para avançar o status')
            ->assertSee('Clique para alternar a prioridade')
            ->assertSee('Checklist da tarefa')
            ->assertDontSee('Abrir checklist da tarefa')
            ->assertDontSee('Exibir checklist da tarefa');
    }

    public function test_task_information_can_be_edited_inline_from_list_view(): void
    {
        $team = Team::factory()->create();
        $originalCategory = Category::factory()->for($team)->create();
        $newCategory = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->forCategory($originalCategory)->create([
            'team_id' => $team->id,
            'title' => 'Titulo original',
            'location' => 'Local original',
            'due_date' => null,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('startInlineEdit', $task->id, 'title')
            ->assertSet('inlineEditValue', 'Titulo original')
            ->set('inlineEditValue', 'Titulo atualizado')
            ->call('saveInlineEdit')
            ->assertHasNoErrors()
            ->assertDispatched('task-inline-updated')
            ->call('startInlineEdit', $task->id, 'location')
            ->set('inlineEditValue', 'Novo local')
            ->call('saveInlineEdit')
            ->call('startInlineEdit', $task->id, 'category_id')
            ->set('inlineEditValue', $newCategory->id)
            ->call('saveInlineEdit')
            ->call('startInlineEdit', $task->id, 'due_date')
            ->set('inlineEditValue', '2026-12-18')
            ->call('saveInlineEdit')
            ->assertSet('inlineEditingTaskId', null);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Titulo atualizado',
            'location' => 'Novo local',
            'category_id' => $newCategory->id,
            'due_date' => '2026-12-18',
        ]);
    }

    public function test_inline_category_options_expand_the_row_without_absolute_overlay(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->create(['team_id' => $team->id, 'category_id' => $category->id]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('startInlineEdit', $task->id, 'category_id')
            ->assertSee('data-testid="inline-category-options"', false)
            ->assertSee('min-w-52', false);
    }

    public function test_inline_priority_and_status_controls_update_task_without_opening_form(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->forCategory($category)->create([
            'team_id' => $team->id,
            'is_urgent' => false,
            'status' => TaskStatus::Pending,
        ]);

        $component = Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('toggleInlineUrgency', $task->id)
            ->assertDispatched('task-inline-updated');

        $this->assertTrue($task->refresh()->is_urgent);

        $component->call('cycleStatus', $task->id)
            ->assertDispatched('task-status-updated');
        $this->assertSame(TaskStatus::InProgress, $task->refresh()->status);

        $component->call('cycleStatus', $task->id);
        $this->assertSame(TaskStatus::Completed, $task->refresh()->status);

        $component->call('cycleStatus', $task->id);
        $this->assertSame(TaskStatus::Pending, $task->refresh()->status);
        $this->assertDatabaseCount('task_histories', 4);
    }

    public function test_inline_controls_cannot_change_task_from_another_team(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        $otherTask = Task::factory()->forTeam($otherTeam)->create([
            'title' => 'Tarefa protegida',
            'is_urgent' => false,
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('startInlineEdit', $otherTask->id, 'title')
            ->assertSet('inlineEditingTaskId', null)
            ->call('toggleInlineUrgency', $otherTask->id)
            ->call('cycleStatus', $otherTask->id);

        $otherTask->refresh();

        $this->assertSame('Tarefa protegida', $otherTask->title);
        $this->assertFalse($otherTask->is_urgent);
        $this->assertSame(TaskStatus::Pending, $otherTask->status);
    }

    public function test_can_create_task_via_livewire(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $assignee = User::factory()->create(['team_id' => $team->id]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Nova Tarefa Teste')
            ->set('form.location', 'Rua de Teste')
            ->set('form.categoryId', $category->id)
            ->set('form.startDate', '2026-09-10')
            ->set('form.dueDate', '2026-09-20')
            ->set('form.isUrgent', true)
            ->set('form.assigneeIds', [$assignee->id])
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('task-saved');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Nova Tarefa Teste',
            'location' => 'Rua de Teste',
            'category_id' => $category->id,
            'start_date' => '2026-09-10',
            'due_date' => '2026-09-20',
            'is_urgent' => true,
            'team_id' => $team->id,
        ]);
        $this->assertDatabaseHas('task_user', [
            'task_id' => Task::query()->where('title', 'Nova Tarefa Teste')->value('id'),
            'user_id' => $assignee->id,
        ]);
    }

    public function test_can_edit_task_via_livewire(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'title' => 'Titulo Antigo',
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->assertSet('form.title', 'Titulo Antigo')
            ->set('form.title', 'Titulo Atualizado')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('task-saved');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Titulo Atualizado',
        ]);
    }

    public function test_assignee_changes_render_history_without_view_error(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->forTeam($team)->create();
        $assignee = User::factory()->forTeam($team)->create(['name' => 'Responsável visível']);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->call('toggleAssignee', $assignee->id)
            ->assertSet('form.assigneeIds', [$assignee->id])
            ->call('save')
            ->assertHasNoErrors()
            ->call('edit', $task->id)
            ->assertSee('Responsável visível')
            ->assertSee('Responsáveis pela tarefa atualizados.');

        $this->assertDatabaseHas('task_user', [
            'task_id' => $task->id,
            'user_id' => $assignee->id,
        ]);
    }

    public function test_can_update_status_via_livewire(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('updateStatus', $task->id, TaskStatus::InProgress->value)
            ->assertHasNoErrors()
            ->assertDispatched('task-status-updated');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::InProgress->value,
        ]);
    }

    public function test_can_filter_task_by_search(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        Task::factory()->forTeam($team)->create([
            'title' => 'Reparo Luz',
            'status' => TaskStatus::Pending,
        ]);
        Task::factory()->forTeam($team)->create([
            'title' => 'Poda Arvore',
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('search', 'Luz')
            ->assertSee('Reparo Luz')
            ->assertDontSee('Poda Arvore');
    }

    public function test_can_manage_checklist_items_via_livewire(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->create([
            'team_id' => $team->id,
            'category_id' => $category->id,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->set('form.newChecklistItem', 'Etapa 1')
            ->call('addChecklistItem')
            ->assertCount('form.checklistItems', 1)
            ->assertSet('form.checklistItems.0.label', 'Etapa 1');

        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Etapa 1',
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->call('toggleChecklistItem', 0)
            ->assertSet('form.checklistItems.0.is_completed', true);

        $this->assertDatabaseHas('task_checklists', [
            'task_id' => $task->id,
            'label' => 'Etapa 1',
            'is_completed' => true,
        ]);
    }

    public function test_generic_save_does_not_change_status_or_create_false_history(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->forCategory($category)->create([
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->set('form.currentStatus', TaskStatus::Completed->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => TaskStatus::Pending->value,
        ]);
        $this->assertDatabaseCount('task_histories', 0);
    }

    public function test_new_task_always_starts_pending_even_if_form_status_is_tampered(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Tarefa protegida')
            ->set('form.categoryId', $category->id)
            ->set('form.currentStatus', TaskStatus::Completed->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa protegida',
            'status' => TaskStatus::Pending->value,
        ]);
    }

    public function test_task_fields_longer_than_database_limits_are_rejected(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', str_repeat('a', 256))
            ->set('form.location', str_repeat('b', 256))
            ->set('form.categoryId', $category->id)
            ->set('form.checklistItems', [[
                'label' => str_repeat('c', 256),
                'is_completed' => false,
            ]])
            ->call('save')
            ->assertHasErrors([
                'form.title' => 'max',
                'form.location' => 'max',
                'form.checklistItems.0.label' => 'max',
            ]);
    }

    public function test_invalid_due_date_is_rejected(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Tarefa com prazo invalido')
            ->set('form.categoryId', $category->id)
            ->set('form.dueDate', '31/02/2026')
            ->call('save')
            ->assertHasErrors(['form.dueDate' => 'date_format']);
    }

    public function test_final_date_cannot_precede_start_date(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Tarefa com período inválido')
            ->set('form.categoryId', $category->id)
            ->set('form.startDate', '2026-09-20')
            ->set('form.dueDate', '2026-09-10')
            ->call('save')
            ->assertHasErrors(['form.dueDate' => 'after_or_equal']);
    }

    public function test_missing_category_is_rejected_before_use_case(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.title', 'Tarefa sem categoria valida')
            ->set('form.categoryId', 999999)
            ->call('save')
            ->assertHasErrors(['form.categoryId' => 'exists']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_quick_category_creation_uses_admin_name_rules(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->admin()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('newCategoryName', 'ab')
            ->call('createNewCategory')
            ->assertHasErrors(['newCategoryName' => 'min'])
            ->set('newCategoryName', str_repeat('a', 256))
            ->call('createNewCategory')
            ->assertHasErrors(['newCategoryName' => 'max']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_can_create_a_category_directly_in_the_task_form_when_none_exist(): void
    {
        $team = Team::factory()->create();
        $admin = User::factory()->admin()->forTeam($team)->create();

        $component = Livewire::actingAs($admin)
            ->test(TaskManager::class, ['team' => $team])
            ->assertSee('Nova categoria')
            ->assertSee('Adicionar')
            ->assertDontSee('Modal de Criacao de Categoria')
            ->set('newCategoryName', 'Manutenção predial')
            ->call('createNewCategory');

        $this->assertDatabaseHas('categories', [
            'team_id' => $team->id,
            'name' => 'Manutenção predial',
        ]);
        $this->assertSame(
            Category::query()->where('team_id', $team->id)->where('name', 'Manutenção predial')->value('id'),
            $component->get('form.categoryId'),
        );
    }

    public function test_team_user_can_select_categories_but_cannot_create_them(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();

        $this->actingAs($user)
            ->get(route('teams.tasks', $team))
            ->assertOk()
            ->assertSee($category->name)
            ->assertDontSee('+ Criar nova categoria...');

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('newCategoryName', 'Categoria indevida')
            ->call('createNewCategory')
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', [
            'team_id' => $team->id,
            'name' => 'Categoria indevida',
        ]);
    }

    public function test_invalid_status_is_rejected_by_status_endpoint(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->forTeam($team)->create([
            'status' => TaskStatus::Pending,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('updateStatus', $task->id, 'invalid')
            ->assertHasErrors(['status']);

        $this->assertSame(TaskStatus::Pending, $task->refresh()->status);
    }
}
