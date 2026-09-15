<?php

namespace Tests\Feature\Tasks;

use App\Livewire\Team\TaskManager;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TaskAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_user_can_upload_and_remove_a_task_attachment(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->forTeam($team)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->set('pendingAttachments', [UploadedFile::fake()->create('relatorio.pdf', 120, 'application/pdf')])
            ->call('uploadAttachments')
            ->assertHasNoErrors()
            ->assertSet('form.attachments.0.name', 'relatorio.pdf');

        $attachment = TaskAttachment::query()->sole();

        $this->assertSame($task->id, $attachment->task_id);
        $this->assertSame($user->id, $attachment->user_id);
        Storage::disk('local')->assertExists($attachment->path);

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->call('edit', $task->id)
            ->call('removeAttachment', $attachment->id)
            ->assertHasNoErrors()
            ->assertSet('form.attachments', []);

        $this->assertDatabaseMissing('task_attachments', ['id' => $attachment->id]);
        Storage::disk('local')->assertMissing($attachment->path);
    }

    public function test_team_user_cannot_attach_a_file_to_another_teams_task(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        $otherTask = Task::factory()->forTeam($otherTeam)->create();

        Livewire::actingAs($user)
            ->test(TaskManager::class, ['team' => $team])
            ->set('form.taskId', $otherTask->id)
            ->set('pendingAttachments', [UploadedFile::fake()->create('privado.pdf', 120, 'application/pdf')])
            ->call('uploadAttachments');

        $this->assertDatabaseCount('task_attachments', 0);
        Storage::disk('local')->assertDirectoryEmpty('task-attachments');
    }

    public function test_team_user_can_download_own_team_attachment_only(): void
    {
        Storage::fake('local');

        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();
        $task = Task::factory()->forTeam($team)->create();
        $otherTask = Task::factory()->forTeam($otherTeam)->create();
        $attachment = TaskAttachment::query()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'path' => 'task-attachments/'.$task->id.'/manual.pdf',
            'original_name' => 'manual.pdf',
            'mime_type' => 'application/pdf',
            'size' => 12,
        ]);
        $otherAttachment = TaskAttachment::query()->create([
            'task_id' => $otherTask->id,
            'user_id' => $user->id,
            'path' => 'task-attachments/'.$otherTask->id.'/externo.pdf',
            'original_name' => 'externo.pdf',
            'mime_type' => 'application/pdf',
            'size' => 12,
        ]);
        Storage::disk('local')->put($attachment->path, 'arquivo');
        Storage::disk('local')->put($otherAttachment->path, 'arquivo');

        $this->actingAs($user)
            ->get(route('teams.tasks.attachments.download', [$team, $task, $attachment]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('teams.tasks.attachments.download', [$otherTeam, $otherTask, $otherAttachment]))
            ->assertForbidden();
    }
}
