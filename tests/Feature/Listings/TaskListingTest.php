<?php

namespace Tests\Feature\Listings;

use App\Application\Tasks\Queries\ListTasks;
use App\Domain\Tasks\TaskStatus;
use App\Livewire\Team\TaskManager;
use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_listing_is_paginated_and_filtered_by_search(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);

        Task::factory()->forTeam($team)->count(12)->create([
            'category_id' => $category->id,
            'title' => 'Tarefa comum',
            'status' => TaskStatus::Pending,
        ]);

        Task::factory()->forTeam($team)->count(3)->create([
            'category_id' => $category->id,
            'title' => 'Reparo urgente',
            'is_urgent' => true,
            'status' => TaskStatus::Pending,
        ]);

        $listing = app(ListTasks::class)->handle($team->id, 'reparo', [], 5);

        $this->assertSame(3, $listing->summary['total']);
        $this->assertSame(3, $listing->summary['urgent']);
        $this->assertSame(3, $listing->tasks->total());
        $this->assertCount(3, $listing->tasks->items());
    }

    public function test_task_listing_summary_counts_completed_records(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);

        Task::factory()->forTeam($team)->count(2)->create([
            'category_id' => $category->id,
            'status' => TaskStatus::Completed,
        ]);

        Task::factory()->forTeam($team)->count(4)->create([
            'category_id' => $category->id,
            'status' => TaskStatus::Pending,
        ]);

        $listing = app(ListTasks::class)->handle($team->id, '', [], 3);

        $this->assertSame(6, $listing->summary['total']);
        $this->assertSame(2, $listing->summary['completed']);
        $this->assertCount(3, $listing->tasks->items());
        $this->assertSame(2, $listing->tasks->lastPage());
    }

    public function test_task_listing_respects_team_scope(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $otherCategory = Category::factory()->create(['team_id' => $otherTeam->id]);

        Task::factory()->forTeam($team)->count(2)->create([
            'category_id' => $category->id,
            'title' => 'tarefas da equipe correta',
            'status' => TaskStatus::Pending,
            'is_urgent' => false,
        ]);

        Task::factory()->forTeam($otherTeam)->count(4)->create([
            'category_id' => $otherCategory->id,
            'title' => 'Tarefa de outra equipe',
            'is_urgent' => true,
            'status' => TaskStatus::Completed,
        ]);

        $listing = app(ListTasks::class)->handle($team->id, '', [], 10);

        $this->assertSame(2, $listing->summary['total']);
        $this->assertSame(0, $listing->summary['urgent']);
        $this->assertSame(0, $listing->summary['completed']);
        $this->assertSame(2, $listing->tasks->total());
    }

    public function test_task_listing_can_filter_by_category_status_and_urgency(): void
    {
        $team = Team::factory()->create();
        $lighting = Category::factory()->create([
            'team_id' => $team->id,
            'name' => 'Iluminacao',
        ]);
        $cleaning = Category::factory()->create([
            'team_id' => $team->id,
            'name' => 'Limpeza',
        ]);

        Task::factory()->forTeam($team)->create([
            'category_id' => $lighting->id,
            'title' => 'Troca de lampada',
            'status' => TaskStatus::Pending,
            'is_urgent' => true,
        ]);

        Task::factory()->forTeam($team)->create([
            'category_id' => $lighting->id,
            'title' => 'Poste concluido',
            'status' => TaskStatus::Completed,
            'is_urgent' => true,
        ]);

        Task::factory()->forTeam($team)->create([
            'category_id' => $cleaning->id,
            'title' => 'Varricao comum',
            'status' => TaskStatus::Completed,
            'is_urgent' => false,
        ]);

        $listing = app(ListTasks::class)->handle($team->id, '', [
            'category_id' => $lighting->id,
            'status' => TaskStatus::Completed->value,
            'urgent' => true,
        ], 10);

        $this->assertSame(1, $listing->summary['total']);
        $this->assertSame(1, $listing->summary['urgent']);
        $this->assertSame(1, $listing->summary['completed']);
        $this->assertSame('Poste concluido', $listing->tasks->items()[0]->title);
    }

    public function test_task_top_summary_cards_work_as_quick_filters(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->create(['team_id' => $team->id]);
        $user = User::factory()->create(['team_id' => $team->id]);

        Task::factory()->forTeam($team)->create([
            'category_id' => $category->id,
            'title' => 'Tarefa urgente',
            'status' => TaskStatus::Pending,
            'is_urgent' => true,
        ]);

        Task::factory()->forTeam($team)->create([
            'category_id' => $category->id,
            'title' => 'Tarefa concluida',
            'status' => TaskStatus::Completed,
            'is_urgent' => false,
        ]);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('applyQuickFilter', 'urgent')
            ->assertSet('quickFilter', 'urgent')
            ->assertSee('Tarefa urgente')
            ->assertDontSee('Tarefa concluida')
            ->call('applyQuickFilter', 'completed')
            ->assertSet('quickFilter', 'completed')
            ->assertSee('Tarefa concluida')
            ->assertDontSee('Tarefa urgente')
            ->call('applyQuickFilter', 'total')
            ->assertSet('quickFilter', 'total')
            ->assertSee('Tarefa urgente')
            ->assertSee('Tarefa concluida');
    }

    public function test_changing_urgency_keeps_task_in_its_original_position(): void
    {
        $team = Team::factory()->create();
        $category = Category::factory()->for($team)->create();
        $user = User::factory()->forTeam($team)->create();
        $olderTask = Task::factory()->forCategory($category)->create([
            'team_id' => $team->id,
            'title' => 'Tarefa mais antiga',
            'status' => TaskStatus::Pending,
            'is_urgent' => false,
            'created_at' => now()->subHour(),
        ]);
        $newerTask = Task::factory()->forCategory($category)->create([
            'team_id' => $team->id,
            'title' => 'Tarefa mais recente',
            'status' => TaskStatus::Pending,
            'is_urgent' => false,
            'created_at' => now(),
        ]);

        $before = app(ListTasks::class)->handle($team->id, '', [], 10);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('toggleInlineUrgency', $olderTask->id)
            ->assertDispatched('task-inline-updated');

        $after = app(ListTasks::class)->handle($team->id, '', [], 10);

        $this->assertSame([$newerTask->id, $olderTask->id], $before->tasks->pluck('id')->all());
        $this->assertSame([$newerTask->id, $olderTask->id], $after->tasks->pluck('id')->all());
        $this->assertTrue($olderTask->refresh()->is_urgent);
    }
}
