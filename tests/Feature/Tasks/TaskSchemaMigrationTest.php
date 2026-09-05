<?php

namespace Tests\Feature\Tasks;

use App\Models\Category;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskSchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_and_rollback_preserve_records_and_relationships(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $category = Category::factory()->create(['team_id' => $team->id]);
        $task = Task::factory()->forCategory($category)->create();
        $task->forceFill(['code' => 'ODS-000042'])->saveQuietly();
        $checklist = $task->checklistItems()->create([
            'label' => 'Conferir documentos',
            'is_completed' => true,
            'sort_order' => 0,
        ]);
        $history = $task->histories()->create([
            'user_id' => $user->id,
            'description' => 'Registro anterior à migração.',
            'metadata' => ['title' => ['from' => 'Anterior', 'to' => $task->title]],
        ]);
        $task->delete();

        $migration = require database_path('migrations/2026_09_05_000000_rename_organization_task_tables.php');
        $migration->down();

        $this->assertFalse(Schema::hasTable('tasks'));
        $this->assertDatabaseHas('service_orders', [
            'id' => $task->id,
            'secretariat_id' => $team->id,
            'code' => 'ODS-000042',
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'secretariat_id' => $team->id]);
        $this->assertDatabaseHas('ods_checklists', ['id' => $checklist->id, 'service_order_id' => $task->id]);
        $this->assertDatabaseHas('ods_histories', ['id' => $history->id, 'service_order_id' => $task->id]);

        // Insert through the legacy schema to exercise migration of existing installations.
        DB::table('ods_checklists')->insert([
            'service_order_id' => $task->id,
            'label' => 'Etapa legada',
            'is_completed' => false,
            'sort_order' => 1,
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasTable('service_orders'));
        $restored = Task::withTrashed()->findOrFail($task->id);
        $this->assertTrue($restored->trashed());
        $this->assertSame('ODS-000042', $restored->code);
        $this->assertTrue($restored->team->is($team));
        $this->assertTrue($restored->category->is($category));
        $this->assertTrue($user->fresh()->team->is($team));
        $this->assertCount(2, $restored->checklistItems);
        $this->assertTrue($restored->checklistItems->first()->is_completed);
        $this->assertTrue($restored->histories->first()->user->is($user));
        $this->assertSame($history->metadata, $restored->histories->first()->metadata);

        $restored->restore();
        $this->assertTrue(Task::query()->search('ODS-000042')->first()->is($restored));
        $newTask = Task::factory()->forCategory($category)->create();
        $this->assertSame(Task::codeFromId($newTask->id), $newTask->code);
        $this->assertStringStartsWith('TASK-', $newTask->code);

        // Physical deletion still follows the original checklist/history foreign keys.
        DB::table('tasks')->where('id', $task->id)->delete();
        $this->assertDatabaseMissing('task_checklists', ['task_id' => $task->id]);
        $this->assertDatabaseMissing('task_histories', ['task_id' => $task->id]);
    }
}
